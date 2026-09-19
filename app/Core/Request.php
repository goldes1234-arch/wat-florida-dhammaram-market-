<?php

namespace App\Core;

class Request
{
    public string $method;
    public string $path;
    public array $query;
    public array $post;
    public array $files;

    private static ?string $basePathCache = null;

    public function __construct(string $method, string $path, array $query, array $post, array $files)
    {
        $this->method = $method;
        $this->path = $path;
        $this->query = $query;
        $this->post = $post;
        $this->files = $files;
    }

    public static function capture(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper((string) $_POST['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $override;
            }
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uriPath = parse_url($uri, PHP_URL_PATH) ?: '/';

        $base = self::basePath();
        if ($base !== '' && str_starts_with($uriPath, $base)) {
            $uriPath = substr($uriPath, strlen($base));
        }
        if ($uriPath === '' || $uriPath[0] !== '/') {
            $uriPath = '/' . $uriPath;
        }
        if (strlen($uriPath) > 1 && str_ends_with($uriPath, '/')) {
            $uriPath = rtrim($uriPath, '/');
        }

        return new self($method, $uriPath, $_GET, $_POST, $_FILES);
    }

    public static function basePath(): string
    {
        if (self::$basePathCache !== null) {
            return self::$basePathCache;
        }

        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        self::$basePathCache = rtrim($scriptDir, '/');

        return self::$basePathCache;
    }

    public function input(string $key, $default = null)
    {
        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    public function trimmed(string $key, string $default = ''): string
    {
        $value = $this->input($key, $default);
        return is_string($value) ? trim($value) : $default;
    }

    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return $file;
    }

    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }
}
