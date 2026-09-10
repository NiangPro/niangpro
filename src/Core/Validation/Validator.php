<?php

namespace Niang\Core\Validation;

final class Validator
{
    private array $errors = [];

    private function __construct(private array $data, private array $rules)
    {
    }

    public static function make(array $data, array $rules): static
    {
        return new static($data, $rules);
    }

    /**
     * @throws ValidationException si une règle échoue
     */
    public function validate(): array
    {
        foreach ($this->rules as $field => $ruleSet) {
            $rules = is_array($ruleSet) ? $ruleSet : explode('|', $ruleSet);
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                if (isset($this->errors[$field])) {
                    break; // une seule erreur affichée par champ, pour rester lisible
                }

                $this->applyRule($field, $value, $rule);
            }
        }

        if ($this->errors) {
            throw new ValidationException($this->errors);
        }

        return array_intersect_key($this->data, $this->rules);
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

    private function applyRule(string $field, mixed $value, string $rule): void
    {
        [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);

        $valid = match ($name) {
            'required' => $value !== null && $value !== '',
            'string' => is_string($value),
            'numeric' => is_numeric($value),
            'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false,
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'min' => is_numeric($value) ? (float) $value >= (float) $param : mb_strlen((string) $value) >= (int) $param,
            'max' => is_numeric($value) ? (float) $value <= (float) $param : mb_strlen((string) $value) <= (int) $param,
            'regex' => preg_match($param, (string) $value) === 1,
            'confirmed' => $value === ($this->data[$field . '_confirmation'] ?? null),
            default => true,
        };

        if (!$valid) {
            $this->errors[$field][] = $this->message($field, $name, $param);
        }
    }

    private function message(string $field, string $rule, ?string $param): string
    {
        return match ($rule) {
            'required' => "Le champ $field est requis.",
            'string' => "Le champ $field doit être une chaîne de caractères.",
            'numeric' => "Le champ $field doit être numérique.",
            'integer' => "Le champ $field doit être un entier.",
            'email' => "Le champ $field doit être une adresse email valide.",
            'min' => "Le champ $field doit contenir au moins $param caractères (ou valoir au moins $param).",
            'max' => "Le champ $field ne doit pas dépasser $param caractères (ou $param).",
            'regex' => "Le format du champ $field est invalide.",
            'confirmed' => "La confirmation du champ $field ne correspond pas.",
            default => "Le champ $field est invalide.",
        };
    }
}
