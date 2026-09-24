<?php

namespace Niang\Core\Validation;

use Niang\Core\Database\DB;
use Niang\Core\Http\UploadedFile;
use Niang\Core\Lang;

final class Validator
{
    /**
     * Types acceptés par la règle `image`. SVG en est volontairement exclu : c'est du XML qui peut
     * contenir du JavaScript — à autoriser explicitement avec mimes:svg si vous en avez besoin.
     */
    private const IMAGE_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'];

    private array $errors = [];

    /**
     * @param array $messages  surcharge de message, par 'champ.règle' (prioritaire) ou juste 'règle'
     * @param array $attributes nom lisible d'un champ, ex: ['email' => 'Adresse email']
     */
    private function __construct(
        private array $data,
        private array $rules,
        private array $messages = [],
        private array $attributes = []
    ) {
    }

    public static function make(array $data, array $rules, array $messages = [], array $attributes = []): static
    {
        return new static($data, $rules, $messages, $attributes);
    }

    /**
     * @throws ValidationException si une règle échoue
     */
    public function validate(): array
    {
        foreach ($this->rules as $field => $ruleSet) {
            $this->validateField($field, is_array($ruleSet) ? $ruleSet : explode('|', $ruleSet));
        }

        if ($this->errors) {
            throw new ValidationException($this->errors);
        }

        return $this->validatedData();
    }

    public function fails(): bool
    {
        try {
            $this->validate();
            return false;
        } catch (ValidationException) {
            return true;
        }
    }

    /** Ne garde que les champs déclarés dans les règles (dépliés pour les champs `items.*...`). */
    private function validatedData(): array
    {
        $result = [];

        foreach (array_keys($this->rules) as $field) {
            $base = explode('.*', $field, 2)[0];

            if (array_key_exists($base, $this->data)) {
                $result[$base] = $this->data[$base];
            }
        }

        return $result;
    }

    private function validateField(string $field, array $rules): void
    {
        if (str_contains($field, '.*')) {
            $this->validateWildcardField($field, $rules);
            return;
        }

        $this->runRules($field, $this->data[$field] ?? null, $rules);
    }

    /** Valide chaque élément d'un tableau : 'items.*.name' ou simplement 'items.*'. */
    private function validateWildcardField(string $field, array $rules): void
    {
        [$arrayField, $rest] = explode('.*', $field, 2);
        $subField = ltrim($rest, '.');

        $items = $this->data[$arrayField] ?? [];

        if (!is_array($items)) {
            return; // pas un tableau : ajoutez la règle 'array' sur $arrayField pour l'exiger explicitement
        }

        foreach ($items as $index => $item) {
            if ($subField !== '') {
                $errorKey = "$arrayField.$index.$subField";
                $value = is_array($item) ? ($item[$subField] ?? null) : null;
            } else {
                $errorKey = "$arrayField.$index";
                $value = $item;
            }

            $this->runRules($errorKey, $value, $rules);
        }
    }

    private function runRules(string $errorKey, mixed $value, array $rules): void
    {
        if (in_array('nullable', $rules, true) && !$this->filled($value)) {
            return;
        }

        // Un upload arrivé en erreur (trop gros pour le serveur, partiel...) : sa vraie raison est
        // plus utile que « le champ doit être une image », et aucune autre règle n'a de sens dessus.
        if ($value instanceof UploadedFile && !$value->isValid()) {
            $this->errors[$errorKey][] = $this->messages["$errorKey.file"] ?? $value->errorMessage();
            return;
        }

        foreach ($rules as $rule) {
            if ($rule === 'nullable') {
                continue;
            }

            if (isset($this->errors[$errorKey])) {
                break; // une seule erreur affichée par champ, pour rester lisible
            }

            $this->applyRule($errorKey, $value, $rule);
        }
    }

    private function filled(mixed $value): bool
    {
        return match (true) {
            $value === null => false,
            is_string($value) => trim($value) !== '',
            is_array($value) => count($value) > 0,
            default => true,
        };
    }

    private function applyRule(string $field, mixed $value, string $rule): void
    {
        [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
        $params = $param !== null ? explode(',', $param) : [];

        $valid = match ($name) {
            'required' => $this->filled($value),
            'required_if' => $this->requiredIf($value, $params),
            'required_with' => $this->requiredWith($value, $params),
            'required_without' => $this->requiredWithout($value, $params),
            'string' => is_string($value),
            'numeric' => is_numeric($value),
            'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false,
            'boolean' => is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false'], true),
            'array' => is_array($value),
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'url' => filter_var($value, FILTER_VALIDATE_URL) !== false,
            'date' => is_string($value) && strtotime($value) !== false,
            'date_format' => is_string($value) && is_string($param) && $this->matchesFormat($value, $param),
            'min' => $this->size($value) >= (float) $param,
            'max' => $this->size($value) <= (float) $param,
            'between' => $this->size($value) >= (float) ($params[0] ?? 0) && $this->size($value) <= (float) ($params[1] ?? 0),
            'in' => in_array((string) $value, $params, true),
            'not_in' => !in_array((string) $value, $params, true),
            'same' => $value === ($this->data[$params[0] ?? ''] ?? null),
            'different' => $value !== ($this->data[$params[0] ?? ''] ?? null),
            'regex' => is_string($param) && preg_match($param, (string) $value) === 1,
            'confirmed' => $value === ($this->data[$field . '_confirmation'] ?? null),
            'unique' => $this->isUnique($value, $params),
            'exists' => $this->exists($value, $params),
            'file' => $value instanceof UploadedFile,
            'image' => $value instanceof UploadedFile && in_array($value->mimeType(), self::IMAGE_MIME_TYPES, true),
            'mimes' => $value instanceof UploadedFile && $this->hasExtension($value, $params),
            'mimetypes' => $value instanceof UploadedFile && $this->hasMimeType($value, $params),
            'dimensions' => $value instanceof UploadedFile && $this->hasDimensions($value, $params),
            default => true,
        };

        if (!$valid) {
            $this->errors[$field][] = $this->message($field, $name, $param, $params, $value instanceof UploadedFile);
        }
    }

    /** Nombre, longueur d'une chaîne, ou taille d'un fichier en kilo-octets (min:, max:, between:). */
    private function size(mixed $value): float
    {
        if ($value instanceof UploadedFile) {
            return $value->size() / 1024;
        }

        return is_numeric($value) ? (float) $value : (float) mb_strlen((string) $value);
    }

    private function matchesFormat(string $value, string $format): bool
    {
        $date = \DateTime::createFromFormat($format, $value);
        return $date !== false && $date->format($format) === $value;
    }

    private function requiredIf(mixed $value, array $params): bool
    {
        $otherValue = $this->data[$params[0] ?? ''] ?? null;

        if ((string) $otherValue !== (string) ($params[1] ?? '')) {
            return true; // condition non remplie : la règle ne s'applique pas ici
        }

        return $this->filled($value);
    }

    private function requiredWith(mixed $value, array $params): bool
    {
        if (!$this->filled($this->data[$params[0] ?? ''] ?? null)) {
            return true;
        }

        return $this->filled($value);
    }

    private function requiredWithout(mixed $value, array $params): bool
    {
        if ($this->filled($this->data[$params[0] ?? ''] ?? null)) {
            return true;
        }

        return $this->filled($value);
    }

    /** unique:table,colonne[,id_à_ignorer[,colonne_id]] — pour ignorer la ligne courante lors d'une modification. */
    private function isUnique(mixed $value, array $params): bool
    {
        $table = $params[0] ?? null;

        if ($table === null) {
            return true;
        }

        $column = $params[1] ?? '';
        $sql = "SELECT 1 FROM $table WHERE $column = ?";
        $bindings = [$value];

        if (isset($params[2])) {
            $idColumn = $params[3] ?? 'id';
            $sql .= " AND $idColumn != ?";
            $bindings[] = $params[2];
        }

        return DB::selectOne($sql, $bindings) === null;
    }

    /** exists:table,colonne (colonne par défaut : 'id') */
    private function exists(mixed $value, array $params): bool
    {
        $table = $params[0] ?? null;

        if ($table === null) {
            return true;
        }

        $column = $params[1] ?? 'id';

        return DB::selectOne("SELECT 1 FROM $table WHERE $column = ?", [$value]) !== null;
    }

    /** mimes:jpg,png,pdf — comparé à l'extension déduite du contenu réel, jamais au nom envoyé. */
    private function hasExtension(UploadedFile $file, array $params): bool
    {
        $extension = $file->extension();
        $allowed = array_map(fn (string $ext) => strtolower(trim($ext)) === 'jpeg' ? 'jpg' : strtolower(trim($ext)), $params);

        return $extension !== null && in_array($extension, $allowed, true);
    }

    /** mimetypes:image/jpeg,application/pdf — accepte aussi un joker de famille (image/*). */
    private function hasMimeType(UploadedFile $file, array $params): bool
    {
        $mime = $file->mimeType();

        foreach ($params as $allowed) {
            $allowed = strtolower(trim($allowed));

            if ($allowed === $mime || (str_ends_with($allowed, '/*') && str_starts_with($mime, substr($allowed, 0, -1)))) {
                return true;
            }
        }

        return false;
    }

    /** dimensions:min_width=100,max_width=2000,min_height=100,max_height=2000 (en pixels, chaque borne facultative). */
    private function hasDimensions(UploadedFile $file, array $params): bool
    {
        $size = @getimagesize($file->path());

        if ($size === false) {
            return false;
        }

        [$width, $height] = $size;

        foreach ($params as $constraint) {
            [$key, $limit] = array_pad(explode('=', $constraint, 2), 2, '0');
            $limit = (int) $limit;

            $ok = match (trim($key)) {
                'min_width' => $width >= $limit,
                'max_width' => $width <= $limit,
                'min_height' => $height >= $limit,
                'max_height' => $height <= $limit,
                'width' => $width === $limit,
                'height' => $height === $limit,
                default => true,
            };

            if (!$ok) {
                return false;
            }
        }

        return true;
    }

    private function message(string $field, string $rule, ?string $param, array $params, bool $isFile = false): string
    {
        if (isset($this->messages["$field.$rule"])) {
            return $this->messages["$field.$rule"];
        }

        if (isset($this->messages[$rule])) {
            return $this->messages[$rule];
        }

        // Libellé : celui passé au Validator, sinon celui de lang/<langue>/validation.php
        // (section 'attributes'), sinon le nom du champ. Pour 'items.0.name', on cherche aussi 'items.*.name'.
        $wildcard = (string) preg_replace('/\.\d+(?=\.|$)/', '.*', $field);
        $label = $this->attributes[$field] ?? $this->attributes[$wildcard] ?? $this->translatedAttribute($field) ?? $this->translatedAttribute($wildcard) ?? $field;

        $key = match (true) {
            $isFile && in_array($rule, ['min', 'max', 'between'], true) => "{$rule}_file",
            in_array($rule, ['required', 'required_if', 'required_with', 'required_without'], true) => 'required',
            Lang::has("validation.$rule") || Lang::has("validation.$rule", Lang::fallbackLocale()) => $rule,
            default => 'invalid',
        };

        $other = $params[0] ?? '';

        return Lang::get("validation.$key", [
            'attribute' => $label,
            'format' => (string) $param,
            'min' => in_array($rule, ['between'], true) ? ($params[0] ?? '') : (string) $param,
            'max' => in_array($rule, ['between'], true) ? ($params[1] ?? '') : (string) $param,
            'values' => (string) $param,
            'other' => $this->attributes[$other] ?? $this->translatedAttribute($other) ?? $other,
            'constraints' => (string) $param,
        ]);
    }

    private function translatedAttribute(string $field): ?string
    {
        $label = Lang::section('validation.attributes')[$field] ?? null;

        return is_string($label) ? $label : null;
    }
}
