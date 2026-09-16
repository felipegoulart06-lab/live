<?php

declare(strict_types=1);

namespace App\Validators;

final class Validator
{
    /** @var array<string, array<int, string>> */
    private array $errors = [];

    /** @param array<string, mixed> $data @param array<string, string> $rules */
    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            foreach (explode('|', $ruleString) as $rule) {
                $this->apply($field, $value, $rule, $data);
            }
        }

        return $this->errors === [];
    }

    /** @return array<string, array<int, string>> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function first(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    private function apply(string $field, mixed $value, string $rule, array $data): void
    {
        [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);

        $failed = match ($name) {
            'required' => $value === null || $value === '',
            'email' => $value !== null && $value !== '' && !filter_var((string) $value, FILTER_VALIDATE_EMAIL),
            'min' => is_string($value) && strlen($value) < (int) $param,
            'max' => is_string($value) && strlen($value) > (int) $param,
            'confirmed' => ($data[$field . '_confirmation'] ?? null) !== $value,
            'in' => !in_array($value, explode(',', (string) $param), true),
            'numeric' => $value !== null && $value !== '' && !is_numeric($value),
            default => false,
        };

        if ($failed) {
            $this->errors[$field][] = $this->message($field, $name, $param);
        }
    }

    private function message(string $field, string $rule, ?string $param): string
    {
        $labels = [
            'display_name' => 'Nome',
            'email' => 'E-mail',
            'password' => 'Senha',
            'status' => 'Status',
            'slug' => 'Slug',
        ];
        $label = $labels[$field] ?? $field;

        return match ($rule) {
            'required' => "{$label} é obrigatório.",
            'email' => 'Informe um e-mail válido.',
            'min' => "{$label} deve ter no mínimo {$param} caracteres.",
            'max' => "{$label} deve ter no máximo {$param} caracteres.",
            'confirmed' => 'A confirmação da senha não confere.',
            'in' => "{$label} selecionado é inválido.",
            default => "{$label} é inválido.",
        };
    }
}
