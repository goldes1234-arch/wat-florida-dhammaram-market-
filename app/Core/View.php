<?php

namespace App\Core;

class View
{
    public static function render(string $template, array $data = [], ?string $layout = null): void
    {
        echo self::renderToString($template, $data, $layout);
    }

    public static function renderToString(string $template, array $data = [], ?string $layout = null): string
    {
        $content = self::renderTemplate($template, $data);

        if ($layout === null) {
            return $content;
        }

        $layoutData = $data;
        $layoutData['content'] = $content;

        return self::renderTemplate('layouts/' . $layout, $layoutData);
    }

    public static function partial(string $template, array $data = []): string
    {
        return self::renderTemplate('partials/' . $template, $data);
    }

    private static function renderTemplate(string $template, array $data): string
    {
        $file = BASE_PATH . '/resources/views/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: {$template}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return (string) ob_get_clean();
    }
}
