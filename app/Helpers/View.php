<?php

declare(strict_types=1);

namespace App\Helpers;

final class View
{
    public static function render(string $view, array $data = [], ?string $layout = null): string
    {
        $content = self::partial($view, $data);
        if ($layout === null) {
            return $content;
        }
        return self::partial($layout, $data + ['content' => $content]);
    }

    public static function partial(string $view, array $data = []): string
    {
        $file = dirname(__DIR__) . '/Views/' . str_replace('.', '/', $view) . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('Vue introuvable : ' . $view);
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }

    public static function echo(string $view, array $data = [], ?string $layout = null): never
    {
        Response::html(self::render($view, $data, $layout));
    }
}

final class Url
{
    public static function to(string $path = ''): string
    {
        return BaseUrl::url($path);
    }

    public static function asset(string $path): string
    {
        return BaseUrl::url('assets/' . ltrim($path, '/'));
    }

    public static function upload(string $file): string
    {
        return BaseUrl::url('uploads/' . ltrim($file, '/'));
    }

    public static function admin(string $path = ''): string
    {
        return BaseUrl::url('admin/' . ltrim($path, '/'));
    }
}

final class H
{
    public static function e(?string $value): string
    {
        return Security::e($value);
    }
}
