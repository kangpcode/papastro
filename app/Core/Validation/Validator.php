<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework
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
 * Papastro Validator — validation engine for request data.
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

    protected function validate(): void
    {
        foreach ($this->rules as $field => $ruleset) {
            $value = $this->data[$field] ?? null;
            $ruleList = is_array($ruleset) ? $ruleset : explode('|', $ruleset);

            $fieldPassed = true;

            foreach ($ruleList as $rule) {
                $params = [];
                if (str_contains($rule, ':')) {
                    [$rule, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                }

                switch ($rule) {
                    case 'required':
                        if ($value === null || $value === '') {
                            $this->addError($field, "The {$field} field is required.");
                            $fieldPassed = false;
                        }
                        break;

                    case 'email':
                        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $this->addError($field, "The {$field} must be a valid email address.");
                            $fieldPassed = false;
                        }
                        break;

                    case 'min':
                        $min = (int) ($params[0] ?? 0);
                        if (is_string($value) && mb_strlen($value) < $min) {
                            $this->addError($field, "The {$field} must be at least {$min} characters.");
                            $fieldPassed = false;
                        } elseif (is_numeric($value) && $value < $min) {
                            $this->addError($field, "The {$field} must be at least {$min}.");
                            $fieldPassed = false;
                        }
                        break;

                    case 'max':
                        $max = (int) ($params[0] ?? 255);
                        if (is_string($value) && mb_strlen($value) > $max) {
                            $this->addError($field, "The {$field} may not be greater than {$max} characters.");
                            $fieldPassed = false;
                        } elseif (is_numeric($value) && $value > $max) {
                            $this->addError($field, "The {$field} may not be greater than {$max}.");
                            $fieldPassed = false;
                        }
                        break;
                }
            }

            if ($fieldPassed && isset($this->data[$field])) {
                $this->validatedData[$field] = $value;
            }
        }
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
