<?php

declare(strict_types=1);

namespace ApiPHP\Http;

use ReflectionMethod;
use ReflectionFunction;
use Closure;

class Router
{
	private array $routes = [];
	private $notFoundHandler = null;
	private string $currentGroupPrefix = '';
	private array $currentGroupMiddleware = [];

	// HTTP Methods
	private const METHOD_GET = 'GET';
	private const METHOD_HEAD = 'HEAD';
	private const METHOD_POST = 'POST';
	private const METHOD_PUT = 'PUT';
	private const METHOD_PATCH = 'PATCH';
	private const METHOD_DELETE = 'DELETE';
	private const METHOD_OPTIONS = 'OPTIONS';

	// Add route methods
	public function get(string $path, mixed $handler, ?array $middleware = null): void
	{
		$this->addRoute(self::METHOD_GET, $path, $handler, $middleware ?? []);
	}

	public function post(string $path, mixed $handler, ?array $middleware = null): void
	{
		$this->addRoute(self::METHOD_POST, $path, $handler, $middleware ?? []);
	}

	public function put(string $path, mixed $handler, ?array $middleware = null): void
	{
		$this->addRoute(self::METHOD_PUT, $path, $handler, $middleware ?? []);
	}

	public function patch(string $path, mixed $handler, ?array $middleware = null): void
	{
		$this->addRoute(self::METHOD_PATCH, $path, $handler, $middleware ?? []);
	}

	public function delete(string $path, mixed $handler, ?array $middleware = null): void
	{
		$this->addRoute(self::METHOD_DELETE, $path, $handler, $middleware ?? []);
	}

	public function options(string $path, mixed $handler, ?array $middleware = null): void
	{
		$this->addRoute(self::METHOD_OPTIONS, $path, $handler, $middleware ?? []);
	}

	/**
	 *| Redirect route.
	 */
	public function redirect(string $path, string $redirectTo): void
	{
		$handler = function () use ($redirectTo) {
			header("Location: " . $redirectTo);
			exit();
		};
		$this->addRoute(self::METHOD_GET, $path, $handler);
	}

	/**
	 *| Set the 404 not found handler.
	 */
	public function add404Handler(callable $handler): void
	{
		$this->notFoundHandler = $handler;
	}

	/**
	 *| Group routes with a prefix and middleware.
	 */
	public function group(string $prefix, array $middleware, Closure $callback): void
	{
		$previousPrefix = $this->currentGroupPrefix;
		$previousMiddleware = $this->currentGroupMiddleware;

		$this->currentGroupPrefix .= $prefix;
		$this->currentGroupMiddleware = array_merge($this->currentGroupMiddleware, $middleware);

		$callback($this);

		// Restore previous group settings
		$this->currentGroupPrefix = $previousPrefix;
		$this->currentGroupMiddleware = $previousMiddleware;
	}

	/**
	 *| Add a route to the router.
	 */
	private function addRoute(string $method, string $path, mixed $handler, array $middleware = []): void
	{
		$fullPath = $this->currentGroupPrefix . $path;
		$fullMiddleware = array_merge($this->currentGroupMiddleware, $middleware);

		$this->routes[] = [
			'method' => $method,
			'path' => $fullPath,
			'handler' => $handler,
			'middleware' => $fullMiddleware,
		];
	}

	/**
	 *| Match a route with parameters.
	 */
	private function match(string $requestPath, string $path, array &$params): bool
	{
		$pathParts = explode('/', trim($path, '/'));
		$requestParts = explode('/', trim($requestPath, '/'));

		if (count($pathParts) !== count($requestParts)) {
			return false;
		}

		foreach ($pathParts as $index => $part) {
			if (preg_match('/\{(\w+):(.+)\}/', $part, $matches)) {
				$paramName = $matches[1];
				$pattern = $matches[2];
				if (!preg_match('/^' . $pattern . '$/', $requestParts[$index])) {
					return false;
				}
				$params[$paramName] = $requestParts[$index];
			} elseif ($part !== $requestParts[$index]) {
				return false;
			}
		}
		return true;
	}

	/**
	 *| Resolve dependencies for the handler.
	 */
	private function resolveDependencies(array $params, mixed $handler): array
	{
		$dependencies = [];

		// Check if the handler is an array (class method reference)
		if (is_array($handler) && isset($handler[0], $handler[1])) {
			// Assuming handler is [Class, Method]
			$reflection = new ReflectionMethod($handler[0], $handler[1]);
		} elseif ($handler instanceof Closure || is_string($handler)) {
			// For closures and function names
			$reflection = new ReflectionFunction($handler);
		} else {
			throw new \InvalidArgumentException('Invalid handler type.');
		}

		foreach ($reflection->getParameters() as $parameter) {
			$paramName = $parameter->getName();
			$paramType = $parameter->getType();

			// Check if parameter is passed as route parameter
			if (array_key_exists($paramName, $params)) {
				$dependencies[] = $params[$paramName];
			}
			// Check if parameter type is a class (dependency injection)
			elseif ($paramType && !$paramType->isBuiltin()) {
				$dependencies[] = new ($paramType->getName())();
			}
			// Use default value if available
			else {
				$dependencies[] = $parameter->isDefaultValueAvailable()
					? $parameter->getDefaultValue()
					: null;
			}
		}

		return $dependencies;
	}



	/**
	 *| Run the router.
	 */
	public function run(string $uri = null, string $method = null): void
	{
		$requestUri = parse_url($uri ?? $_SERVER['REQUEST_URI']);
		$requestPath = $requestUri['path'];
		$method = $method ?? $_SERVER['REQUEST_METHOD'];

		$params = [];

		foreach ($this->routes as $route) {
			// Check for matching route based on method and path
			if ($route['method'] === $method && $this->match($requestPath, $route['path'], $params)) {
				// Execute middleware if available
				foreach ($route['middleware'] as $middleware) {
					$middlewareInstance = new $middleware;

					if (!$middlewareInstance->handle(new Request, new Response)) {
						return;
					}
				}

				// Execute route handler with dependencies resolved
				$callback = $route['handler'];
				$dependencies = $this->resolveDependencies($params, $callback);

				if (is_array($callback)) {
					[$controller, $method] = $callback;
					if (class_exists($controller) && method_exists($controller, $method)) {
						call_user_func_array([new $controller(), $method], $dependencies);
						return;
					}
				} else {
					call_user_func_array($callback, $dependencies);
					return;
				}
			}
		}

		// 404 Not Found handling
		header("HTTP/1.0 404 Not Found");
		if ($this->notFoundHandler) {
			call_user_func($this->notFoundHandler);
		} else {
			echo '404 Not Found';
		}
	}
}
