<?php

use PHPUnit\Framework\TestCase;

class MemoryTestCase extends TestCase
{
    protected function setUp(): void
    {
        // Load Composer autoload
        require_once __DIR__ . '/../vendor/autoload.php';

        // Load environment variables
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();

        // Load bootstrap to include all classes
        require_once __DIR__ . '/../inc/bootstrap.php';
    }
}