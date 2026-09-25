<?php

declare(strict_types=1);

namespace App\Validators;

final class Validator
{
    /** @var array<string, array<int, string>> */
    private array $errors = [];

    /** @param array<string, string> $labels */
    public function __construct(private readonly array $labels = [])
    {
    }

    /** @param array<string, mixed> $data @param array<string, string> $rules */
    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            if (is_string($value)) {
                $value = trim($value);
            }
            $rulesList = explode('|', $ruleString);
            $isEmpty = $value === null || $value === '' || $value === [];
            if ($isEmpty && !in_array('required', $rulesList, true)) {
                continue;
            }
            foreach ($rulesList as $rule) {
                if ($this->apply($field, $value, $rule, $data)) {
                    break;
                }
            }
        }

        return $this->errors === [];
    }

    /** @return array<string, array<int, string>> */
    public function errors(): array
    {
        return $this->errors;
    }

    /** Returns true when the rule failed (stops further checks on the field). */
    private function apply(string $field, mixed $value, string $rule, array $data): bool
    {
        [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
        $text = is_scalar($value) ? (string) $value : '';

        $failed = match ($name) {
            'required' => $value === null || $value === '' || $value === [],
            'email' => !filter_var($text, FILTER_VALIDATE_EMAIL) || mb_strlen($text) > 190,
            'min' => mb_strlen($text) < (int) $param,
            'max' => mb_strlen($text) > (int) $param,
            'confirmed' => ($data[$field . '_confirmation'] ?? null) !== $value,
            'in' => !in_array($text, explode(',', (string) $param), true),
            'integer' => filter_var($text, FILTER_VALIDATE_INT) === false,
            'between' => (function () use ($text, $param): bool {
                [$min, $max] = array_map('intval', explode(',', (string) $param));
                $int = filter_var($text, FILTER_VALIDATE_INT);

                return $int === false || $int < $min || $int > $max;
            })(),
            'date' => !preg_match('/^\d{4}-\d{2}-\d{2}$/', $text) || !checkdate((int) substr($text, 5, 2), (int) substr($text, 8, 2), (int) substr($text, 0, 4)),
            'future' => $text < date('Y-m-d'),
            'time' => !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $text),
            'url' => !filter_var($text, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $text),
            'money' => parse_money($text) === null,
            'password' => mb_strlen($text) < 8 || !preg_match('/[A-Za-z]/', $text) || !preg_match('/\d/', $text),
            default => false,
        };

        if ($failed) {
            $this->errors[$field][] = $this->message($field, $name, $param);
        }

        return $failed;
    }

    private function message(string $field, string $rule, ?string $param): string
    {
        $label = $this->labels[$field] ?? ucfirst(str_replace('_', ' ', $field));

        return match ($rule) {
            'required' => "Preencha o campo {$label}.",
            'email' => 'Informe um e-mail válido.',
            'min' => "{$label} deve ter pelo menos {$param} caracteres.",
            'max' => "{$label} deve ter no máximo {$param} caracteres.",
            'confirmed' => 'A confirmação da senha não confere.',
            'in' => "Escolha uma opção válida em {$label}.",
            'integer' => "{$label} deve ser um número inteiro.",
            'between' => "{$label} deve estar entre " . str_replace(',', ' e ', (string) $param) . '.',
            'date' => "Informe uma data válida em {$label}.",
            'future' => "{$label} não pode estar no passado.",
            'time' => "Informe um horário válido em {$label}.",
            'url' => "Informe um link válido (https://...) em {$label}.",
            'money' => "Informe um valor válido em {$label}, por exemplo 450,00.",
            'password' => 'A senha precisa de pelo menos 8 caracteres, com letras e números.',
            default => "{$label} é inválido.",
        };
    }
}
