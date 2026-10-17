<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

$databaseUrl = $_SERVER['DATABASE_URL'] ?? $_ENV['DATABASE_URL'] ?? null;
if (is_string($databaseUrl) && str_starts_with($databaseUrl, 'mysql:')) {
    $parts = parse_url($databaseUrl);
    $databaseName = ltrim($parts['path'] ?? '', '/');
    $testDatabaseName = str_ends_with($databaseName, '_test') ? $databaseName : $databaseName.'_test';
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d', $parts['host'] ?? '127.0.0.1', $parts['port'] ?? 3306),
        rawurldecode($parts['user'] ?? 'root'),
        rawurldecode($parts['pass'] ?? ''),
    );
    $pdo->exec(sprintf(
        'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
        str_replace('`', '``', $testDatabaseName),
    ));
}
