<?php

declare(strict_types=1);

namespace ApiPHP\Http;

use ReflectionMethod;
use ReflectionFunction;
use Closure;

/**
 * Router class for handling HTTP routing in PHP applications.
 * This router supports different HTTP methods, middleware, route grouping, and versioning.
 */
class Router
{
	/**
	 * @var array $routes Array storing all the defined routes in the router.
	 */
	private array $routes = [];

	private array $errors = [];

	/**
	 * @var callable|null $notFoundHandler Handler for 404 Not Found response.
	 */
	private $notFoundHandler = null;

	/**
	 * @var string $currentGroupPrefix Prefix for route groups, used for grouping related routes.
	 */
	private string $currentGroupPrefix = '';

	/**
	 * @var array $currentGroupMiddleware Middleware applied to the current route group.
	 */
	private array $currentGroupMiddleware = [];

	/**
	 * @var string $version Current API version, typically used for versioned APIs.
	 */
	private string $version = "";

	// HTTP Methods Constants
	private const METHOD_GET = 'GET';
	private const METHOD_HEAD = 'HEAD';
	private const METHOD_POST = 'POST';
	private const METHOD_PUT = 'PUT';
	private const METHOD_PATCH = 'PATCH';
	private const METHOD_DELETE = 'DELETE';
	private const METHOD_OPTIONS = 'OPTIONS';

	/**
	 * Set up the API's version. You can use it with the version() function.
	 * 
	 * @param string $version The version of the API.
	 * @return void
	 */
	public function setVersion(?string $version = ""): void
	{
		$this->version = $version;
	}

	/**
	 * Register a GET route.
	 *
	 * @param string $path The route path.
	 * @param mixed $handler The handler (callback or class method) for the route.
	 * @param array|null $middleware Optional middleware for the route.
	 * @return void
	 */
	public function get(string $path, mixed $handler, ?array $middleware = null): void
	{
		$this->addRoute(self::METHOD_GET, $path, $handler, $middleware ?? []);
	}

	/**
	 * Register a POST route.
	 *
	 * @param string $path The route path.
	 * @param mixed $handler The handler for the route.
	 * @param array|null $middleware Optional middleware for the route.
	 * @return void
	 */
	public function post(string $path, mixed $handler, ?array $middleware = null): void
	{
		$this->addRoute(self::METHOD_POST, $path, $handler, $middleware ?? []);
	}

	/**
	 * Register a PUT route.
	 *
	 * @param string $path The route path.
	 * @param mixed $handler The handler for the route.
	 * @param array|null $middleware Optional middleware for the route.
	 * @return void
	 */
	public function put(string $path, mixed $handler, ?array $middleware = null): void
	{
		$this->addRoute(self::METHOD_PUT, $path, $handler, $middleware ?? []);
	}

	/**
	 * Register a PATCH route.
	 *
	 * @param string $path The route path.
	 * @param mixed $handler The handler for the route.
	 * @param array|null $middleware Optional middleware for the route.
	 * @return void
	 */
	public function patch(string $path, mixed $handler, ?array $middleware = null): void
	{
		$this->addRoute(self::METHOD_PATCH, $path, $handler, $middleware ?? []);
	}

	/**
	 * Register a DELETE route.
	 *
	 * @param string $path The route path.
	 * @param mixed $handler The handler for the route.
	 * @param array|null $middleware Optional middleware for the route.
	 * @return void
	 */
	public function delete(string $path, mixed $handler, ?array $middleware = null): void
	{
		$this->addRoute(self::METHOD_DELETE, $path, $handler, $middleware ?? []);
	}

	/**
	 * Register an OPTIONS route.
	 *
	 * @param string $path The route path.
	 * @param mixed $handler The handler for the route.
	 * @param array|null $middleware Optional middleware for the route.
	 * @return void
	 */
	public function options(string $path, mixed $handler, ?array $middleware = null): void
	{
		$this->addRoute(self::METHOD_OPTIONS, $path, $handler, $middleware ?? []);
	}

	/**
	 * Register a redirect route. When the path is requested, it redirects to another path.
	 *
	 * @param string $path The path to be redirected.
	 * @param string $redirectTo The target path to redirect to.
	 * @return void
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
	 * Set the handler for 404 Not Found responses.
	 *
	 * @param callable $handler The handler to be executed when no route matches the request.
	 * @return void
	 */
	public function add404Handler(callable $handler): void
	{
		$this->notFoundHandler = $handler;
	}

	/**
	 * Group routes together under a common prefix and shared middleware.
	 *
	 * @param string $prefix The prefix to be applied to all routes in this group.
	 * @param Closure $callback A closure where the grouped routes are defined.
	 * @param array $middleware Optional middleware to apply to all routes in this group.
	 * @return void
	 */
	public function group(string $prefix, Closure $callback, array $middleware = [], ?string $version = ''): void
	{
		$previousPrefix = $this->currentGroupPrefix;
		$previousMiddleware = $this->currentGroupMiddleware;

		$this->currentGroupPrefix .= $prefix;
		$this->currentGroupMiddleware = array_merge($this->currentGroupMiddleware, $middleware);

		$callback($this);

		// Restore previous group settings after the callback is executed
		$this->currentGroupPrefix = $previousPrefix;
		$this->currentGroupMiddleware = $previousMiddleware;
	}

	/**
	 * Define a versioned group of routes.
	 *
	 * @param string $version The version of the API.
	 * @param Closure $callback A closure where the versioned routes are defined.
	 * @param array $middleware Optional middleware for the versioned routes.
	 * @param string $prefix Optional custom prefix for versioned routes. If not provided, it defaults to '/api/v{version}'.
	 * @return void
	 */
	public function version(Closure $callback, array $middleware = [], ?string $prefix = '', ?string $version = ''): void
	{
		$previousPrefix = $this->currentGroupPrefix;
		$previousMiddleware = $this->currentGroupMiddleware;

		// Use provided prefix or default to '/api/v{version}'
		if (!empty($prefix)) {
			$this->currentGroupPrefix = $prefix;
		} else {
			$this->currentGroupPrefix = !empty($version) ? "/api/v{$version}" : "/api/v{$this->version}";
		}

		$this->currentGroupMiddleware = array_merge($this->currentGroupMiddleware, $middleware);

		$callback($this);

		// Restore previous group settings
		$this->currentGroupPrefix = $previousPrefix;
		$this->currentGroupMiddleware = $previousMiddleware;
	}

	/**
	 * Add a route to the router.
	 *
	 * @param string $method The HTTP method for the route (e.g., GET, POST).
	 * @param string $path The path for the route.
	 * @param mixed $handler The handler (callback or class method) for the route.
	 * @param array $middleware Optional middleware for the route.
	 * @return void
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
	 * Match a route against a given request path and extract any parameters.
	 *
	 * @param string $requestPath The request URI path.
	 * @param string $path The route path pattern to match against.
	 * @param array $params Reference array to store extracted parameters if the route matches.
	 * @return bool True if the route matches, false otherwise.
	 */
	private function match(string $requestPath, string $path, array &$params): bool
	{
		$pathParts = explode('/', trim($path, '/'));
		$requestParts = explode('/', trim($requestPath, '/'));

		// If the number of parts doesn't match, the paths don't match
		if (count($pathParts) !== count($requestParts)) {
			return false;
		}

		// Check each part of the path
		foreach ($pathParts as $index => $part) {
			// Check if it's a dynamic parameter
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
	 * Resolve dependencies for the route handler.
	 *
	 * @param array $params Route parameters.
	 * @param mixed $handler The handler for the route (either a closure or class method).
	 * @return array The resolved dependencies.
	 */
	private function resolveDependencies(array $params, mixed $handler): array
	{
		$dependencies = [];

		// If handler is a class method reference (array format)
		if (is_array($handler) && isset($handler[0], $handler[1])) {
			$reflection = new ReflectionMethod($handler[0], $handler[1]);
		} elseif ($handler instanceof Closure || is_string($handler)) {
			// For closures and function names
			$reflection = new ReflectionFunction($handler);
		} else {
			throw new \InvalidArgumentException('Invalid handler type.');
		}

		// Resolve dependencies for each parameter in the handler
		foreach ($reflection->getParameters() as $parameter) {
			$paramName = $parameter->getName();
			$paramType = $parameter->getType();

			// Check if parameter is passed as a route parameter
			if (array_key_exists($paramName, $params)) {
				$dependencies[] = $params[$paramName];
			}
			// Check if parameter type is a class (for dependency injection)
			elseif ($paramType && !$paramType->isBuiltin()) {
				$dependencies[] = new ($paramType->getName())();
			}
			// Use default value if available, otherwise null
			else {
				$dependencies[] = $parameter->isDefaultValueAvailable()
					? $parameter->getDefaultValue()
					: null;
			}
		}

		return $dependencies;
	}

	/**
	 * Output information about the current routes and handlers.
	 */
	public function info(?bool $showRoutes = false): void
	{
		$has404Handler = $this->notFoundHandler ? "true" : "false";
		$methodsCount = ['GET' => 0, 'POST' => 0, 'PUT' => 0, 'PATCH' => 0, 'DELETE' => 0, 'OPTIONS' => 0, 'HEAD' => 0];
		$version = !empty($this->version) ? $this->version : "N/A";

		// Count the routes per method
		foreach ($this->routes as $route) {
			$methodsCount[$route['method']]++;
		}

		// Display route information
		print "<pre>";
		print "Routes count: " . count($this->routes) . "\n";
		print "Has 404 handler: {$has404Handler}\n";
		print "Current app version: {$version}\n";
		print "Counted methods: ";
		print_r($methodsCount);
		if ($showRoutes) {
			print "Routes: ";
			print_r($this->routes);
		}
		print "</pre>";
	}

	/**
	 * Run the router and match the incoming request to the defined routes.
	 *
	 * @param string|null $uri The request URI to match against. Defaults to the current request URI.
	 * @param string|null $method The HTTP method to match. Defaults to the current request method.
	 */
	public function run(string $uri = null, string $method = null): void
	{
		$requestUri = parse_url($uri ?? $_SERVER['REQUEST_URI']);
		$requestPath = $requestUri['path'];
		$method = $method ?? $_SERVER['REQUEST_METHOD'];

		$params = [];

		// Check each route for a match
		foreach ($this->routes as $route) {
			if ($route['method'] === $method && $this->match($requestPath, $route['path'], $params)) {
				// Execute any middleware for the route
				foreach ($route['middleware'] as $middleware) {
					$middlewareInstance = new $middleware;
					if (!$middlewareInstance->handle(new Request, new Response)) {
						return;
					}
				}

				// Execute the route handler with resolved dependencies
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

		// If no route matched, return a 404 response
		header("HTTP/1.0 404 Not Found");
		if ($this->notFoundHandler) {
			call_user_func($this->notFoundHandler);
		} else {
			echo '404 Not Found';
		}
	}
}
