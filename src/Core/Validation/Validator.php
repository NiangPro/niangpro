<?php

namespace Niang\Core\Validation;

use Niang\Core\Database\DB;

final class Validator
{
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
            default => true,
        };

        if (!$valid) {
            $this->errors[$field][] = $this->message($field, $name, $param, $params);
        }
    }

    private function size(mixed $value): float
    {
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

    private function message(string $field, string $rule, ?string $param, array $params): string
    {
        if (isset($this->messages["$field.$rule"])) {
            return $this->messages["$field.$rule"];
        }

        if (isset($this->messages[$rule])) {
            return $this->messages[$rule];
        }

        $label = $this->attributes[$field] ?? $field;

        return match ($rule) {
            'required', 'required_if', 'required_with', 'required_without' => "Le champ $label est requis.",
            'string' => "Le champ $label doit être une chaîne de caractères.",
            'numeric' => "Le champ $label doit être numérique.",
            'integer' => "Le champ $label doit être un entier.",
            'boolean' => "Le champ $label doit être vrai ou faux.",
            'array' => "Le champ $label doit être un tableau.",
            'email' => "Le champ $label doit être une adresse email valide.",
            'url' => "Le champ $label doit être une URL valide.",
            'date' => "Le champ $label doit être une date valide.",
            'date_format' => "Le champ $label doit respecter le format $param.",
            'min' => "Le champ $label doit contenir au moins $param caractères (ou valoir au moins $param).",
            'max' => "Le champ $label ne doit pas dépasser $param caractères (ou $param).",
            'between' => "Le champ $label doit être compris entre {$params[0]} et {$params[1]}.",
            'in' => "Le champ $label doit être l'une des valeurs suivantes : $param.",
            'not_in' => "Le champ $label ne doit pas être l'une des valeurs suivantes : $param.",
            'same' => "Le champ $label doit être identique à {$params[0]}.",
            'different' => "Le champ $label doit être différent de {$params[0]}.",
            'regex' => "Le format du champ $label est invalide.",
            'confirmed' => "La confirmation du champ $label ne correspond pas.",
            'unique' => "Cette valeur du champ $label est déjà utilisée.",
            'exists' => "La valeur sélectionnée pour $label est invalide.",
            default => "Le champ $label est invalide.",
        };
    }
}
