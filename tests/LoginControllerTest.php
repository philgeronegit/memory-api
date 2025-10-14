<?php

require_once __DIR__ . '/MemoryTestCase.php';

class LoginControllerTest extends MemoryTestCase
{
    private $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new LoginController();
    }

    public function testLoginControllerCanBeInstantiated()
    {
        $this->assertInstanceOf(LoginController::class, $this->controller);
    }

    public function testLoginControllerHasModel()
    {
        $reflection = new ReflectionClass(LoginController::class);
        $property = $reflection->getProperty('model');
        $property->setAccessible(true);
        $model = $property->getValue($this->controller);
        $this->assertInstanceOf(LoginModel::class, $model);
    }
}