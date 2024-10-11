<?php

declare(strict_types=1);

namespace ApiPHP\Http;

use Exception;
use ApiPHP\Http\Exceptions\InvalidArgumentException;
use ApiPHP\Http\Exceptions\InvalidFormatException;

/**
 * The Request class handles incoming HTTP requests.
 * It provides utility methods to retrieve request method, URI, headers, and body.
 */
class Request
{

	/** HTTP method constants */
	public const GET = 'GET';
	public const POST = 'POST';
	public const HEAD = 'HEAD';
	public const PUT = 'PUT';
	public const PATCH = 'PATCH';
	public const DELETE = 'DELETE';
	public const OPTIONS = 'OPTIONS';

	/**
	 * Retrieves the HTTP request method (GET, POST, etc.).
	 * 
	 * @return string The HTTP method used in the request. Defaults to 'GET' if not set.
	 */
	public static function getMethod(): string
	{
		return $_SERVER['REQUEST_METHOD'] ?? self::GET;
	}

	/**
	 * Retrieves the requested URI.
	 * 
	 * @return string The URI of the current request.
	 */
	public static function getUri(): string
	{
		return $_SERVER['REQUEST_URI'];
	}

	/**
	 * Retrieves all HTTP headers from the request.
	 * 
	 * @return array An associative array of request headers.
	 */
	public static function getHeaders(): array
	{
		return getallheaders();
	}

	/**
	 * Retrieves a specific HTTP header by key.
	 * 
	 * @param string $key The name of the header to retrieve.
	 * @param mixed $default The default value to return if the header is not found.
	 * 
	 * @return mixed The value of the header, or the default value if not present.
	 */
	public static function getHeader(string $key, mixed $default = null): mixed
	{
		$headers = self::getHeaders();
		return $headers[$key] ?? $default;
	}

	/**
	 * Retrieves the raw request body and decodes it as JSON.
	 * 
	 * @return array The decoded JSON body as an associative array.
	 */
	public static function getBody(): array
	{
		return json_decode(file_get_contents('php://input'), true);
	}

	/**
	 * Checks if the request method matches the provided key.
	 * Optionally, executes the $true callback if the method matches, or the $else callback if it does not.
	 * 
	 * @param string $key The HTTP method to check (GET, POST, etc.).
	 * @param callable|null $true The callback to execute if the method matches (optional).
	 * @param callable|null $else The callback to execute if the method does not match (optional).
	 * 
	 * @return bool|callable Returns true if the method matches, false otherwise.
	 * Executes the corresponding callback if provided.
	 * 
	 * @throws InvalidArgumentException If the provided callbacks are not callable.
	 */
	public static function isMethod(string $key, callable $true = null, callable $else = null): bool|callable
	{
		$isMethod = self::getMethod() === $key;

		if ($isMethod && $true) {
			is_callable($true) ? $true() : throw new InvalidArgumentException("The given [true] method is not callable!");;
		}

		if (!$isMethod && $else) {
			is_callable($else) ? $else() : throw new InvalidArgumentException("The given [true] method is not callable!");;
		}

		return $isMethod;
	}

	/**
	 * Retrieves the raw request body and decodes it as JSON.
	 * Throws an exception if the JSON is invalid.
	 * 
	 * @return array The decoded JSON body as an associative array.
	 * 
	 * @throws Exception If the JSON is invalid or cannot be decoded.
	 */
	public static function getJsonBody(): array
	{
		$body = file_get_contents('php://input');
		$data = json_decode($body, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new InvalidFormatException("Invalid JSON format!");
		}

		return $data;
	}

	/**
	 * Checks if the Content-Type of the request matches the provided type.
	 * 
	 * @param string $type The Content-Type to check (e.g., 'application/json').
	 * 
	 * @return bool True if the Content-Type matches, false otherwise.
	 */
	public static function isContentType(string $type): bool
	{
		return self::getHeader('Content-Type') === $type;
	}

	/**
	 * Checks if the Content-Type of the request is 'application/json'.
	 * 
	 * @return bool True if the Content-Type is 'application/json', false otherwise.
	 */
	public static function isJSON(): bool
	{
		return self::isContentType('application/json');
	}

	/**
	 * Checks if the Content-Type of the request is 'multipart/form-data'.
	 * 
	 * @return bool True if the Content-Type is 'multipart/form-data', false otherwise.
	 */
	public static function isFormData(): bool
	{
		return self::isContentType('multipart/form-data');
	}
}
