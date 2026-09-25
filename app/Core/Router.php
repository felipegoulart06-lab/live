<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{method:string, pattern:string, handler:array|string|\Closure, middleware:array}> */
    private array $routes = [];

    /** @var array<int, string> */
    private array $groupMiddleware = [];

    private string $groupPrefix = '';

    /** @param \Closure(self):void $callback */
    public function group(string $prefix, array $middleware, \Closure $callback): void
    {
        $previousPrefix = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        $joined = trim($previousPrefix . '/' . trim($prefix, '/'), '/');
        $this->groupPrefix = $joined;
        $this->groupMiddleware = array_merge($previousMiddleware, $middleware);
        $callback($this);

        $this->groupPrefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    public function get(string $path, array|string|\Closure $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array|string|\Closure $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function put(string $path, array|string|\Closure $handler, array $middleware = []): void
    {
        $this->add('PUT', $path, $handler, $middleware);
    }

    public function patch(string $path, array|string|\Closure $handler, array $middleware = []): void
    {
        $this->add('PATCH', $path, $handler, $middleware);
    }

    public function delete(string $path, array|string|\Closure $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    private function add(string $method, string $path, array|string|\Closure $handler, array $middleware): void
    {
        $full = trim($this->groupPrefix . '/' . ltrim($path, '/'), '/');
        $pattern = $full === '' ? '/' : '/' . $full;

        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
        ];
    }

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method()) {
                continue;
            }

            $params = $this->match($route['pattern'], $request->path());
            if ($params === null) {
                continue;
            }

            $request->setParams($params);

            return (new Pipeline($route['middleware']))->handle($request, function (Request $request) use ($route): Response {
                return $this->invoke($route['handler'], $request);
            });
        }

        throw HttpException::notFound();
    }

    /** @return array<string, string>|null */
    private function match(string $pattern, string $path): ?array
    {
        $pattern = $pattern === '/' ? '/' : rtrim($pattern, '/');
        $path = $path === '/' ? '/' : rtrim($path, '/');

        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#u';

        if (!preg_match($regex, $path, $matches)) {
            return null;
        }

        return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
    }

    private function invoke(array|string|\Closure $handler, Request $request): Response
    {
        if ($handler instanceof \Closure) {
            return $handler($request);
        }
        if (is_string($handler)) {
            [$class, $method] = explode('@', $handler);
        } else {
            [$class, $method] = $handler;
        }

        $controller = new $class();

        return $controller->{$method}($request);
    }
}
