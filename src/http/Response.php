<?php declare(strict_types=1);

namespace ApiPHP\Http;

/**
 * The Response class handles the HTTP response to be sent to the client.
 * It allows setting the response status code, headers, body, and sending the response.
 */
class Response
{
	/** @var int $status_code The HTTP status code of the response (e.g., 200, 404). */
	protected int $status_code;

	/** @var array $headers Associative array of HTTP headers for the response. */
	protected array $headers = [];

	/** @var mixed $body The content/body of the response, which can be a string, array, etc. */
	protected mixed $body;

	/**
	 * Constructor for the Response class.
	 * Initializes the status code and body of the response.
	 *
	 * @param int $status_code The HTTP status code of the response. Defaults to 200 (OK).
	 * @param mixed $body The body/content of the response. Defaults to null.
	 */
	public function __construct(int $status_code = 200, mixed $body = null)
	{
		$this->status_code = $status_code;
		$this->body = $body;
	}

	/**
	 * Sets the HTTP status code for the response.
	 *
	 * @param int $code The HTTP status code to set (e.g., 200, 404).
	 * @return self The current Response instance for method chaining.
	 */
	public function setStatusCode(int $code): self
	{
		$this->status_code = $code;
		return $this;
	}

	/**
	 * Retrieves the current HTTP status code of the response.
	 *
	 * @return int The HTTP status code of the response.
	 */
	public function getStatusCode(): int
	{
		return $this->status_code;
	}

	/**
	 * Adds a header to the response.
	 *
	 * @param string $key The name of the header (e.g., 'Content-Type').
	 * @param string $value The value of the header (e.g., 'application/json').
	 * @return self The current Response instance for method chaining.
	 */
	public function addHeader(string $key, string $value): self
	{
		$this->headers[$key] = $value;
		return $this;
	}

	/**
	 * Retrieves all the headers for the response.
	 *
	 * @return array An associative array of all response headers.
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	/**
	 * Sets the body/content of the response.
	 *
	 * @param mixed $body The body of the response (e.g., a string, array).
	 * @return self The current Response instance for method chaining.
	 */
	public function setBody(mixed $body): self
	{
		$this->body = $body;
		return $this;
	}

	/**
	 * Retrieves the body/content of the response.
	 *
	 * @return mixed The body of the response (could be a string, array, etc.).
	 */
	public function getBody(): mixed
	{
		return $this->body;
	}

	/**
	 * Sends the response to the client.
	 * It sets the status code, headers, and body before sending the response.
	 *
	 * @param bool $reset If true, resets the response data (status code, headers, and body) after sending.
	 */
	public function send(bool $reset = false): void
	{
		// Set the HTTP status code
		http_response_code($this->status_code);

		// Send each header to the client
		foreach ($this->headers as $key => $value) {
			header("{$key}: {$value}");
		}

		// Send the response body (if present), encoding it as JSON if it's an array
		if (!empty($this->body)) {
			echo is_array($this->body) ? json_encode($this->body) : $this->body;
		}

		// Optionally reset the response data
		if ($reset) {
			$this->reset();
		}
	}

	/**
	 * Resets the response status code, headers, and body to their default values.
	 * This can be useful when sending multiple responses in a single process.
	 */
	protected function reset(): void
	{
		$this->status_code = 200;
		$this->headers = [];
		$this->body = null;
	}
}
