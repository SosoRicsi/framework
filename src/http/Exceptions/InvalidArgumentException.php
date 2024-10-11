<?php

declare(strict_types=1);

namespace ApiPHP\Http\Exceptions;

use Exception;
use Throwable;

/**
 * Custom InvalidArgumentException class.
 * Extends the built-in Exception class to add custom functionality.
 */
class InvalidArgumentException extends Exception
{
	/**
	 * Extra property to store additional data related to the exception.
	 * @var mixed
	 */
	private mixed $additionalData;

	/**
	 * Constructor to initialize the exception with a message, code, previous exception, and additional data.
	 *
	 * @param string $message The exception message.
	 * @param int $code The exception code.
	 * @param Throwable|null $previous The previous exception used for chaining.
	 * @param mixed $additionalData Any additional data related to the exception.
	 */
	public function __construct(string $message = "Invalid argument", int $code = 0, ?Throwable $previous = null, mixed $additionalData = null)
	{
		// Call the parent constructor to set the message, code, and previous exception
		parent::__construct($message, $code, $previous);

		// Set the additional data
		$this->additionalData = $additionalData;
	}

	/**
	 * Get the additional data associated with this exception.
	 *
	 * @return mixed The additional data stored in the exception.
	 */
	public function getAdditionalData(): mixed
	{
		return $this->additionalData;
	}

	/**
	 * Override the __toString() method to provide a custom exception message.
	 *
	 * @return string The custom string representation of the exception.
	 */
	public function __toString(): string
	{
		$baseMessage = parent::__toString();
		$additionalInfo = $this->additionalData ? ' Additional Data: ' . print_r($this->additionalData, true) : '';
		return $baseMessage . $additionalInfo;
	}
}
