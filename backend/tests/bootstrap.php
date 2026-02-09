<?php

/**
 * PHPUnit Bootstrap File
 *
 * CRITICAL: This file forces SQLite in-memory database for ALL tests,
 * regardless of the environment variables set in Docker or elsewhere.
 * This ensures tests NEVER touch the real database.
 */

// Force SQLite in-memory BEFORE anything else loads
// These override any environment variables from Docker
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = ':memory:';
$_SERVER['DB_CONNECTION'] = 'sqlite';
$_SERVER['DB_DATABASE'] = ':memory:';

// Load Composer autoloader
require __DIR__.'/../vendor/autoload.php';
