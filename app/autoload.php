<?php

declare(strict_types=1);

/**
 * Autoload PSR-4 simplifié (pas de Composer sur cet hébergement).
 * App\Foo\Bar -> app/Foo/Bar.php
 * Les classes secondaires vivant dans le même fichier que leur classe
 * principale sont résolues via $extraClasses.
 */

spl_autoload_register(static function (string $class): void {
    static $extra = [
        'App\\Helpers\\Request' => 'Helpers/Request.php',
        'App\\Helpers\\BaseUrl' => 'Helpers/Request.php',
        'App\\Helpers\\App' => 'Helpers/Request.php',
        'App\\Helpers\\Money' => 'Helpers/Security.php',
        'App\\Helpers\\Format' => 'Helpers/Security.php',
        'App\\Helpers\\Url' => 'Helpers/View.php',
        'App\\Helpers\\H' => 'Helpers/View.php',
        'App\\Repositories\\LoginAttemptRepository' => 'Repositories/AdminRepository.php',
        'App\\Repositories\\LogRepository' => 'Repositories/AdminRepository.php',
    ];

    if (!str_starts_with($class, 'App\\')) {
        return;
    }

    $root = dirname(__DIR__) . '/app/';

    if (isset($extra[$class])) {
        require_once $root . $extra[$class];
        return;
    }

    $relative = substr($class, strlen('App\\'));
    $file = $root . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});
