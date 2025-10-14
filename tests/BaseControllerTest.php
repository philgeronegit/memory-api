<?php

require_once __DIR__ . '/MemoryTestCase.php';

class BaseControllerTest extends MemoryTestCase
{
    private $controller;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a mock model for testing
        $mockModel = $this->createMock(UserModel::class);
        $this->controller = new BaseController($mockModel);
    }

    public function testSanitizeInputString()
    {
        $reflection = new ReflectionClass(BaseController::class);
        $method = $reflection->getMethod('sanitizeInput');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, '<b>Hello</b> <i>World</i>');
        $this->assertEquals('Hello World', $result);
    }

    public function testSanitizeInputArray()
    {
        $reflection = new ReflectionClass(BaseController::class);
        $method = $reflection->getMethod('sanitizeInput');
        $method->setAccessible(true);

        $input = ['name' => '<b>John</b>', 'email' => 'john@example.com'];
        $result = $method->invoke($this->controller, $input);
        $expected = ['name' => 'John', 'email' => 'john@example.com'];
        $this->assertEquals($expected, $result);
    }

    public function testSanitizeInputNonString()
    {
        $reflection = new ReflectionClass(BaseController::class);
        $method = $reflection->getMethod('sanitizeInput');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, 123);
        $this->assertEquals(123, $result);
    }
}