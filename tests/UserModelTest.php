<?php

require_once __DIR__ . '/MemoryTestCase.php';

class UserModelTest extends MemoryTestCase
{
    public function testUserModelCanBeInstantiated()
    {
        $userModel = new UserModel();
        $this->assertInstanceOf(UserModel::class, $userModel);
    }

    public function testGetAllReturnsArray()
    {
        $userModel = new UserModel();
        $result = $userModel->getAll(['limit' => 10]);
        $this->assertIsArray($result);
    }

    public function testGetOneReturnsObject()
    {
        $userModel = new UserModel();
        $result = $userModel->getOne(1); // Assuming user with ID 1 exists
        $this->assertIsObject($result);
    }
}