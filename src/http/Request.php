<?php

declare(strict_types=1);

namespace ApiPHP\Http;

use Exception;
use ApiPHP\Http\Exceptions\InvalidArgumentException;
use ApiPHP\Http\Exceptions\InvalidFormatException;

/**
 * The Request class handles the incoming HTTP request.
 * It provides utility methods to retrieve the request method, URI, headers, and body.
 */
class Request
{
	/** @var string $method The HTTP method (e.g., GET, POST) used in the request. */
	protected string $method;

	/** @var string $uri The URI of the current request. */
	protected string $uri;

	/** @var array $headers Associative array of HTTP headers from the request. */
	protected array $headers;

	/** @var mixed $body The raw request body, as a string. */
	protected mixed $body;

	/**
	 * Constructor for the Request class.
	 * Initializes the request method, URI, headers, and body from the incoming HTTP request.
	 */
	public function __construct()
	{
		// Set the HTTP method (default: GET if not present)
		$this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

		// Set the requested URI (default: '/' if not present)
		$this->uri = $_SERVER['REQUEST_URI'] ?? '/';

		// Get all headers from the request
		$this->headers = getallheaders();

		// Get the raw request body
		$this->body = file_get_contents('php://input');
	}

	/**
	 * Retrieves the HTTP request method (GET, POST, etc.).
	 *
	 * @return string The HTTP method used in the request.
	 */
	public function getMethod(): string
	{
		return $this->method;
	}

	/**
	 * Retrieves the requested URI.
	 *
	 * @return string The URI of the current request.
	 */
	public function getUri(): string
	{
		return $this->uri;
	}

	/**
	 * Retrieves all HTTP headers from the request.
	 *
	 * @return array An associative array of request headers.
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	/**
	 * Retrieves a specific HTTP header by key.
	 *
	 * @param string $key The name of the header to retrieve.
	 * @param mixed $default The default value to return if the header is not found.
	 *
	 * @return mixed The value of the header, or the default value if not found.
	 */
	public function getHeader(string $key, mixed $default = null): mixed
	{
		return $this->headers[$key] ?? $default;
	}

	/**
	 * Retrieves the raw request body and decodes it as JSON.
	 * This method is useful for processing JSON payloads sent in the request body.
	 *
	 * @return mixed The decoded JSON body as an associative array, or null if not JSON.
	 */
	public function getBody(?string $key = null): mixed
	{
		if ($key != null) {
			return json_decode($this->body, true)[$key];
		}

		return json_decode($this->body, true);
	}

	/**
	 * Retrieves and validates the raw request body as JSON.
	 * Throws an exception if the body is not valid JSON.
	 *
	 * @return array The decoded JSON body as an associative array.
	 *
	 * @throws InvalidFormatException If the body is not valid JSON.
	 */
	public function getJsonBody(?string $key = null): array|string
	{
		// Decode the request body as JSON
		$data = json_decode($this->body, true);

		// Check for JSON decoding errors
		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new InvalidFormatException("Invalid JSON format!");
		}


		if ($key != null) {
			return $data[$key];
		}
		
		return $data;
	}

	/**
	 * Checks if the request method matches the provided key (e.g., GET, POST).
	 * Optionally, executes callbacks if the method matches or does not match.
	 *
	 * @param string $key The HTTP method to check (e.g., GET, POST).
	 * @param callable|null $true Callback to execute if the method matches (optional).
	 * @param callable|null $else Callback to execute if the method does not match (optional).
	 *
	 * @return bool True if the method matches, false otherwise.
	 *
	 * @throws InvalidArgumentException If the provided callbacks are not callable.
	 */
	public function isMethod(string $key, callable $true = null, callable $else = null): bool
	{
		// Check if the current request method matches the given key
		$isMethod = $this->method === $key;

		// If the method matches and a valid "true" callback is provided, execute it
		if ($isMethod && $true) {
			if (!is_callable($true)) {
				throw new InvalidArgumentException("The given [true] method is not callable!");
			}
			$true();
		}

		// If the method does not match and a valid "else" callback is provided, execute it
		if (!$isMethod && $else) {
			if (!is_callable($else)) {
				throw new InvalidArgumentException("The given [else] method is not callable!");
			}
			$else();
		}

		return $isMethod;
	}

	/**
	 * Checks if the Content-Type of the request matches the provided type.
	 *
	 * @param string $type The Content-Type to check (e.g., 'application/json').
	 *
	 * @return bool True if the Content-Type matches, false otherwise.
	 */
	public function isContentType(string $type): bool
	{
		return $this->getHeader('Content-Type') === $type;
	}

	/**
	 * Checks if the Content-Type of the request is 'application/json'.
	 *
	 * @return bool True if the Content-Type is 'application/json', false otherwise.
	 */
	public function isJSON(): bool
	{
		return $this->isContentType('application/json');
	}

	/**
	 * Checks if the Content-Type of the request is 'multipart/form-data'.
	 *
	 * @return bool True if the Content-Type is 'multipart/form-data', false otherwise.
	 */
	public function isFormData(): bool
	{
		return $this->isContentType('multipart/form-data');
	}
}
