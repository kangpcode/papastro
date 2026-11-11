<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v3.0.0
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Papastro\Validation;

use Exception;

/**
 * Papastro Validator — Comprehensive validation engine for request data.
 */
class Validator
{
    protected array $data;
    protected array $rules;
    protected array $errors = [];
    protected array $validatedData = [];

    public function __construct(array $data, array $rules)
    {
        $this->data  = $data;
        $this->rules = $rules;
        $this->validate();
    }

    public static function make(array $data, array $rules): static
    {
        return new static($data, $rules);
    }

    protected function validate(): void
    {
        foreach ($this->rules as $field => $ruleset) {
            $value = $this->data[$field] ?? null;
            $ruleList = is_array($ruleset) ? $ruleset : explode('|', (string) $ruleset);

            $isNullable = in_array('nullable', $ruleList, true);
            if ($isNullable && ($value === null || $value === '')) {
                if (array_key_exists($field, $this->data)) {
                    $this->validatedData[$field] = null;
                }
                continue;
            }

            $fieldPassed = true;

            foreach ($ruleList as $rule) {
                if ($rule === 'nullable' || $rule === '') {
                    continue;
                }

                $params = [];
                if (str_contains($rule, ':')) {
                    [$rule, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                }

                if (!$this->checkRule($field, $value, $rule, $params)) {
                    $fieldPassed = false;
                }
            }

            if ($fieldPassed && array_key_exists($field, $this->data)) {
                $this->validatedData[$field] = $value;
            }
        }
    }

    protected function checkRule(string $field, mixed $value, string $rule, array $params): bool
    {
        switch ($rule) {
            case 'required':
                if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                    $this->addError($field, "The {$field} field is required.");
                    return false;
                }
                break;

            case 'string':
                if ($value !== null && !is_string($value)) {
                    $this->addError($field, "The {$field} must be a string.");
                    return false;
                }
                break;

            case 'integer':
            case 'int':
                if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, "The {$field} must be an integer.");
                    return false;
                }
                break;

            case 'numeric':
                if ($value !== null && $value !== '' && !is_numeric($value)) {
                    $this->addError($field, "The {$field} must be a number.");
                    return false;
                }
                break;

            case 'boolean':
            case 'bool':
                if ($value !== null && !in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false'], true)) {
                    $this->addError($field, "The {$field} field must be true or false.");
                    return false;
                }
                break;

            case 'array':
                if ($value !== null && !is_array($value)) {
                    $this->addError($field, "The {$field} must be an array.");
                    return false;
                }
                break;

            case 'email':
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "The {$field} must be a valid email address.");
                    return false;
                }
                break;

            case 'url':
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, "The {$field} must be a valid URL.");
                    return false;
                }
                break;

            case 'min':
                $min = (float) ($params[0] ?? 0);
                if (is_string($value) && mb_strlen($value) < $min) {
                    $this->addError($field, "The {$field} must be at least {$min} characters.");
                    return false;
                } elseif (is_numeric($value) && (float) $value < $min) {
                    $this->addError($field, "The {$field} must be at least {$min}.");
                    return false;
                } elseif (is_array($value) && count($value) < $min) {
                    $this->addError($field, "The {$field} must have at least {$min} items.");
                    return false;
                }
                break;

            case 'max':
                $max = (float) ($params[0] ?? 255);
                if (is_string($value) && mb_strlen($value) > $max) {
                    $this->addError($field, "The {$field} may not be greater than {$max} characters.");
                    return false;
                } elseif (is_numeric($value) && (float) $value > $max) {
                    $this->addError($field, "The {$field} may not be greater than {$max}.");
                    return false;
                } elseif (is_array($value) && count($value) > $max) {
                    $this->addError($field, "The {$field} may not have more than {$max} items.");
                    return false;
                }
                break;

            case 'between':
                $min = (float) ($params[0] ?? 0);
                $max = (float) ($params[1] ?? 255);
                $len = is_string($value) ? mb_strlen($value) : (is_numeric($value) ? (float) $value : 0);
                if ($len < $min || $len > $max) {
                    $this->addError($field, "The {$field} must be between {$min} and {$max}.");
                    return false;
                }
                break;

            case 'in':
                if ($value !== null && !in_array((string) $value, $params, true)) {
                    $this->addError($field, "The selected {$field} is invalid.");
                    return false;
                }
                break;

            case 'not_in':
                if ($value !== null && in_array((string) $value, $params, true)) {
                    $this->addError($field, "The selected {$field} is invalid.");
                    return false;
                }
                break;

            case 'alpha':
                if ($value !== null && $value !== '' && !preg_match('/^[\pL\pM]+$/u', (string) $value)) {
                    $this->addError($field, "The {$field} must only contain letters.");
                    return false;
                }
                break;

            case 'alpha_num':
                if ($value !== null && $value !== '' && !preg_match('/^[\pL\pM\pN]+$/u', (string) $value)) {
                    $this->addError($field, "The {$field} must only contain letters and numbers.");
                    return false;
                }
                break;

            case 'alpha_dash':
                if ($value !== null && $value !== '' && !preg_match('/^[\pL\pM\pN_-]+$/u', (string) $value)) {
                    $this->addError($field, "The {$field} must only contain letters, numbers, dashes, and underscores.");
                    return false;
                }
                break;

            case 'confirmed':
                $confirmVal = $this->data["{$field}_confirmation"] ?? null;
                if ($value !== $confirmVal) {
                    $this->addError($field, "The {$field} confirmation does not match.");
                    return false;
                }
                break;

            case 'same':
                $other = $params[0] ?? '';
                if (($this->data[$other] ?? null) !== $value) {
                    $this->addError($field, "The {$field} and {$other} must match.");
                    return false;
                }
                break;

            case 'different':
                $other = $params[0] ?? '';
                if (($this->data[$other] ?? null) === $value) {
                    $this->addError($field, "The {$field} and {$other} must be different.");
                    return false;
                }
                break;

            case 'date':
                if ($value !== null && $value !== '' && strtotime((string) $value) === false) {
                    $this->addError($field, "The {$field} is not a valid date.");
                    return false;
                }
                break;

            case 'uuid':
                if ($value !== null && $value !== '' && !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', (string) $value)) {
                    $this->addError($field, "The {$field} must be a valid UUID.");
                    return false;
                }
                break;

            case 'unique':
                if ($value !== null && $value !== '') {
                    $table = $params[0] ?? '';
                    $column = $params[1] ?? $field;
                    $ignoreId = $params[2] ?? null;
                    if ($table && function_exists('app') && app()->bound(\Papastro\ORM\Connection::class)) {
                        $qb = app(\Papastro\ORM\Connection::class)->table($table)->where($column, $value);
                        if ($ignoreId !== null) {
                            $qb->where('id', '!=', $ignoreId);
                        }
                        if ($qb->exists()) {
                            $this->addError($field, "The {$field} has already been taken.");
                            return false;
                        }
                    }
                }
                break;

            case 'exists':
                if ($value !== null && $value !== '') {
                    $table = $params[0] ?? '';
                    $column = $params[1] ?? $field;
                    if ($table && function_exists('app') && app()->bound(\Papastro\ORM\Connection::class)) {
                        $exists = app(\Papastro\ORM\Connection::class)->table($table)->where($column, $value)->exists();
                        if (!$exists) {
                            $this->addError($field, "The selected {$field} is invalid.");
                            return false;
                        }
                    }
                }
                break;
        }

        return true;
    }

    protected function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function validated(): array
    {
        return $this->validatedData;
    }
}

/**
 * ValidationException — thrown when validation fails.
 */
class ValidationException extends Exception
{
    protected Validator $validator;

    public function __construct(Validator $validator)
    {
        $this->validator = $validator;
        $firstError = current(current($validator->errors())) ?: 'Validation failed.';
        parent::__construct($firstError, 422);
    }

    public function getValidator(): Validator
    {
        return $this->validator;
    }

    public function errors(): array
    {
        return $this->validator->errors();
    }
}
