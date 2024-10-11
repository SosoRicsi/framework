<?php

declare(strict_types=1);

namespace ApiPHP\Http\Exceptions;

use Exception;
use Throwable;

/**
 * Custom InvalidFormatException class.
 * Thrown when the input format is invalid (e.g., not a valid JSON).
 */
class InvalidFormatException extends Exception
{
	/**
	 * Constructor to initialize the exception with a message and optional additional data.
	 *
	 * @param string $message The exception message.
	 * @param int $code The exception code.
	 * @param Throwable|null $previous The previous exception used for chaining.
	 */
	public function __construct(string $message = "Invalid format", int $code = 0, ?Throwable $previous = null)
	{
		// Call the parent constructor to set the message, code, and previous exception
		parent::__construct($message, $code, $previous);
	}

	/**
	 * Override the __toString() method to provide a custom string representation of the exception.
	 *
	 * @return string The custom string representation of the exception.
	 */
	public function __toString(): string
	{
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}
}
