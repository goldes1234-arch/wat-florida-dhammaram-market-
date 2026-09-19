<?php

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $groupMiddleware = [];
    private string $groupPrefix = '';

    public function get(string $pattern, array $action, array $middleware = []): void
    {
        $this->add('GET', $pattern, $action, $middleware);
    }

    public function post(string $pattern, array $action, array $middleware = []): void
    {
        $this->add('POST', $pattern, $action, $middleware);
    }

    public function group(array $options, \Closure $callback): void
    {
        $previousPrefix = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->groupPrefix = $previousPrefix . ($options['prefix'] ?? '');
        $this->groupMiddleware = array_merge($previousMiddleware, $options['middleware'] ?? []);

        $callback($this);

        $this->groupPrefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    private function add(string $method, string $pattern, array $action, array $middleware): void
    {
        $this->routes[] = [
            'method' => $method,
            'pattern' => $this->groupPrefix . $pattern,
            'action' => $action,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
        ];
    }

    private function compile(string $pattern): string
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        return '#^' . $regex . '$#u';
    }

    public function dispatch(Request $request): void
    {
        $csrfExempt = ['/stripe/webhook'];

        if ($request->isPost() && !in_array($request->path, $csrfExempt, true)) {
            if (!Csrf::verify($request->post['_csrf'] ?? null)) {
                http_response_code(419);
                echo 'Invalid or expired form submission (CSRF). Please go back, refresh, and try again.';
                return;
            }
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }
            if (!preg_match($this->compile($route['pattern']), $request->path, $matches)) {
                continue;
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            foreach ($route['middleware'] as $middleware) {
                $result = $this->runMiddleware($middleware);
                if ($result === false) {
                    return;
                }
            }

            [$controllerClass, $methodName] = $route['action'];
            $controller = new $controllerClass();
            $controller->$methodName($request, ...array_values($params));
            return;
        }

        http_response_code(404);
        View::render('errors/404', [], 'public');
    }

    private function runMiddleware(string $middleware): bool
    {
        $base = rtrim(Request::basePath(), '/');

        if ($middleware === 'auth') {
            if (!Auth::check()) {
                Flash::error(Lang::get('auth.login_required'));
                header('Location: ' . $base . '/admin/login');
                return false;
            }
        }

        if ($middleware === 'guest') {
            if (Auth::check()) {
                header('Location: ' . $base . '/admin');
                return false;
            }
        }

        if ($middleware === 'super_admin') {
            if (!Auth::isSuperAdmin()) {
                http_response_code(403);
                echo 'Forbidden: super admin only.';
                return false;
            }
        }

        if ($middleware === 'staff_or_admin') {
            if (Auth::isCheckinOnly()) {
                header('Location: ' . $base . '/admin/checkin');
                return false;
            }
        }

        return true;
    }
}
