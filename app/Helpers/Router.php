<?php

declare(strict_types=1);

namespace App\Helpers;

final class Router
{
    /** @var array<int,array{method:string,pattern:string,regex:string,handler:callable|array,middleware:array}> */
    private array $routes = [];

    private string $prefix = '';

    /** @var array<int,callable|string> */
    private array $groupMiddleware = [];

    public function group(string $prefix, callable $fn, array $middleware = []): void
    {
        $previousPrefix = $this->prefix;
        $previousMw = $this->groupMiddleware;

        $this->prefix = rtrim($previousPrefix . '/' . trim($prefix, '/'), '/');
        $this->groupMiddleware = array_merge($previousMw, $middleware);

        $fn($this);

        $this->prefix = $previousPrefix;
        $this->groupMiddleware = $previousMw;
    }

    public function get(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    private function add(string $method, string $path, callable|array $handler, array $middleware): void
    {
        $full = rtrim($this->prefix . '/' . trim($path, '/'), '/');
        if ($full === '') {
            $full = '/';
        }

        $this->routes[] = [
            'method' => $method,
            'pattern' => $full,
            'regex' => $this->compile($full),
            'handler' => $handler,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
        ];
    }

    /**
     * Convertit "/product/{slug}" en regex nommée.
     * Contraintes regex possibles : "/product/{slug:[a-z0-9-]+}".
     */
    private function compile(string $pattern): string
    {
        $parts = preg_split(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}#',
            $pattern,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );
        if ($parts === false) {
            $parts = [$pattern];
        }

        $regex = '';
        $total = count($parts);
        for ($i = 0; $i < $total; $i++) {
            if ($i % 3 === 0) {
                $regex .= preg_quote($parts[$i], '#');
            } elseif ($i % 3 === 1) {
                $rule = ($parts[$i + 1] ?? '') !== '' ? $parts[$i + 1] : '[^/]+';
                $regex .= '(?P<' . $parts[$i] . '>' . $rule . ')';
                $i++;
            }
        }

        return '#^' . $regex . '/?$#';
    }

    public function dispatch(): void
    {
        $method = Request::method();
        $path = Request::path();
        $allowed = [];

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            if ($route['method'] !== $method) {
                $allowed[] = $route['method'];
                continue;
            }

            foreach ($route['middleware'] as $mw) {
                self::runMiddleware($mw);
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $this->call($route['handler'], $params);
            return;
        }

        if ($allowed !== []) {
            Response::abort(405, 'Méthode non autorisée pour cette page.');
        }

        Response::abort(404, 'Page introuvable.');
    }

    private static function runMiddleware(callable|string $mw): void
    {
        if (is_string($mw) && method_exists($mw, 'handle')) {
            $mw::handle();
            return;
        }
        $mw();
    }

    private function call(callable|array $handler, array $params): void
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $controller = new $class();
            $controller->$method(...array_values($params));
            return;
        }
        $handler(...array_values($params));
    }
}
