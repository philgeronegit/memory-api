<?php

require_once __DIR__ . '/MemoryTestCase.php';

class RoleModelTest extends MemoryTestCase
{
    public function testRoleModelCanBeInstantiated()
    {
        $roleModel = new RoleModel();
        $this->assertInstanceOf(RoleModel::class, $roleModel);
    }

    public function testGetAllReturnsArray()
    {
        $roleModel = new RoleModel();
        $result = $roleModel->getAll(['limit' => 10]);
        $this->assertIsArray($result);
    }

    public function testGetOneReturnsObject()
    {
        $roleModel = new RoleModel();
        $result = $roleModel->getOne(1); // Assuming role with ID 1 exists
        $this->assertIsObject($result);
    }
}
