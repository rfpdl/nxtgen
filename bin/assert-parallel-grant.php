<?php

declare(strict_types=1);

/**
 * Fail fast, and legibly, if MySQL cannot create the per-worker test databases that
 * `artisan test --parallel` needs.
 *
 * Every worker gets its OWN database — nxtgen_test_test_1 upwards, created and dropped per run.
 * MySQL privileges are PER DATABASE, so `GRANT ALL ON nxtgen_test.*` says nothing about
 * nxtgen_test_test_1. Without the pattern grant every worker fails at setup and you get one
 * identical "Access denied ... to database 'nxtgen_test_test_1'" per test — hundreds of lines naming
 * the symptom and never the cause. Palo lost an afternoon to exactly this on MySQL.
 *
 * PDO rather than `docker exec mysql`, deliberately: this has to work both against the local
 * container and against a CI service container, where there is no `nxtgen-mysql` to exec into and no
 * mysql client installed. PHP is present wherever the suite runs.
 */
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '33316';
$user = getenv('DB_USERNAME') ?: 'nxtgen';
$pass = getenv('DB_PASSWORD') ?: 'nxtgen';
$probe = (getenv('DB_DATABASE') ?: 'nxtgen_test').'_test_probe';

try {
    $pdo = new PDO("mysql:host={$host};port={$port}", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$probe}`");
    $pdo->exec("DROP DATABASE `{$probe}`");
} catch (PDOException $e) {
    fwrite(STDERR, <<<TEXT

  MySQL cannot create the per-worker test databases that --parallel needs.

    {$e->getMessage()}

  Grant the pattern for this volume:

    docker exec nxtgen-app-mysql mysql -uroot -pnxtgen -e \\
      "GRANT ALL PRIVILEGES ON \`nxtgen\\\\_test%\`.* TO 'nxtgen'@'%'; FLUSH PRIVILEGES;"

  Or recreate it, which applies docker/mysql/init-databases.sql:

    docker compose down && docker volume rm nxtgen_mysqldata && make up


TEXT);

    exit(1);
}
