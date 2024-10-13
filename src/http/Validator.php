<?php

declare(strict_types=1);

namespace ApiPHP\Http;

class Validator
{
	/**
	 * List of validation errors.
	 *
	 * @var array
	 */
	protected array $errors = [];

	/* -------------------------- strings -------------------------- */

	/**
	 * Validates that a field is not empty.
	 *
	 * @param string $field The name of the field.
	 * @param mixed $value The value of the field.
	 * @return bool True if the field is not empty, otherwise false.
	 */
	public function required(string $field, mixed $value): bool
	{
		if (empty($value)) {
			$this->addError($field, "The [{$field}] field is required.");
			
			return false;
		}
		
		return true;
	}

	/**
	 * Validates that a field contains a valid email address.
	 *
	 * @param string $field The name of the field.
	 * @param string $value The value of the field.
	 * @return bool True if the field is a valid email address, otherwise false.
	 */
	public function email(string $field, string $value): bool
	{
		if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
			$this->addError($field, "The [{$field}] field must be a valid email address.");
			
			return false;
		}
		
		return true;
	}

	/**
	 * Validates that a field contains a string.
	 *
	 * @param string $field The name of the field.
	 * @param mixed $value The value of the field.
	 * @return bool True if the field contains a string, otherwise false.
	 */
	public function string(string $field, mixed $value): bool
	{
		if (!is_string($value)) {
			$this->addError($field, "The [{$field}] field must be a string.");
			
			return false;
		}
		
		return true;
	}

	/* -------------------------- numeric -------------------------- */

	/**
	 * Validates that a field contains a numeric value.
	 *
	 * @param string $field The name of the field.
	 * @param mixed $value The value of the field.
	 * @return bool True if the field contains a numeric value, otherwise false.
	 */
	public function numeric(string $field, mixed $value): bool
	{
		if (!is_numeric($value)) {
			$this->addError($field, "The [{$field}] field must be a number.");
			
			return false;
		}
		
		return true;
	}

	/**
	 * Validates that a numeric value falls within a specified range.
	 *
	 * @param string $field The name of the field.
	 * @param mixed $value The value of the field.
	 * @param int $min The minimum allowed value.
	 * @param int $max The maximum allowed value.
	 * @return bool True if the value is within the range, otherwise false.
	 */
	public function between(string $field, mixed $value, int $min, int $max): bool
	{
		if (!is_numeric($value)) {
			$this->addError($field, "The [{$field}] field must be a numeric value.");
			
			return false;
		}

		if ($value < $min || $value > $max) {
			$this->addError($field, "The [{$field}] field must be between {$min} and {$max}.");
			
			return false;
		}
		
		return true;
	}

	/**
	 * Validates if the fields value is under the maximum value.
	 * 
	 * @param string $field The name of the field.
	 * @param mixed $value The value of the field.
	 * @param int $max The maximum allowed value.
	 * @return bool True if the value is under the maximum value.
	 */
	public function under(string $field, mixed $value, int $max): bool
	{
		if (!is_numeric($value)) {
			$this->addError($field, "The [{$field}] field must be a numeric value.");
			
			return false;
		}

		if ($value > $max) {
			$this->addError($field, "The [{$field}] field must be under [{$max}].");
			
			return false;
		}

		return true;
	}

	/**
	 * Validate if the fields value is bigger than the maximum valu.
	 * 
	 * @param string $field The name of the field.
	 * @param mixed $value The value of the field.
	 * @param int $min The minimum value of the field.
	 * @return bool
	 */
	public function upper(string $field, mixed $value, int $min): bool
	{
		if (!is_numeric($value)) {
			$this->addError($field, "The [{$field}] field must be a numeric value.");
			
			return false;
		}

		if ($value < $min) {
			$this->addError($field, "The [{$field}] field must be bigger than [{$min}].");

			return false;
		}

		return true;
	}

	/* -------------------------- dates -------------------------- */

	/* -------------------------- arrays -------------------------- */

	/**
	 * Validates that a field contains an array.
	 *
	 * @param string $field The name of the field.
	 * @param mixed $value The value of the field.
	 * @return bool True if the field contains an array, otherwise false.
	 */
	public function array(string $field, mixed $value): bool
	{
		if (!is_array($value)) {
			$this->addError($field, "The [{$field}] field must be an array.");
			
			return false;
		}
		
		return true;
	}

	/**
	 * Validates that a value is contained in an array.
	 *
	 * @param string $field The name of the field.
	 * @param mixed $value The value to check.
	 * @param array $array The array to check against.
	 * @return bool True if the value is in the array, otherwise false.
	 */
	public function array_contains(string $field, mixed $value, array $array): bool
	{
		if (!in_array($value, $array)) {
			$this->addError($field, "The [{$value}] element is not in the [{$array}] array.");
			
			return false;
		}

		return true;
	}

	/**
	 * Adds an error message to the list of errors for a specific field.
	 *
	 * @param string $field The name of the field.
	 * @param string $message The error message.
	 * @return void
	 */
	protected function addError(string $field, string $message): void
	{
		$this->errors[$field][] = $message;
	}

	/**
	 * Checks if any validation errors exist.
	 *
	 * @return bool True if there are errors, otherwise false.
	 */
	public function hasErrors(): bool
	{
		return !empty($this->errors);
	}

	/**
	 * Retrieves all validation errors.
	 *
	 * @return array An array of errors.
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}

	/**
	 * Validates the provided data against the given rules.
	 *
	 * @param array $data The data to validate.
	 * @param array $rules An associative array of validation rules.
	 * @return bool True if the data passes all validation rules, otherwise false.
	 */
	public function validate(array $data, array $rules): bool
	{
		foreach ($rules as $field => $ruleSet) {
			$value = $data[$field] ?? null;

			foreach ($ruleSet as $rule) {
				if (is_string($rule)) {
					if (!$this->$rule($field, $value)) {
						break; // Skip to the next field if validation fails.
					}
				} elseif (is_array($rule)) {
					// Handle rules with parameters.
					$ruleName = $rule[0];
					$params = array_slice($rule, 1);
					if (!call_user_func_array([$this, $ruleName], array_merge([$field, $value], $params))) {
						break;
					}
				}
			}
		}

		return !$this->hasErrors();
	}
}
