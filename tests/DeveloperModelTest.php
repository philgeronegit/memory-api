<?php

require_once __DIR__ . '/MemoryTestCase.php';

class DeveloperModelTest extends MemoryTestCase
{
    public function testDeveloperModelCanBeInstantiated()
    {
        $developerModel = new DeveloperModel();
        $this->assertInstanceOf(DeveloperModel::class, $developerModel);
    }

    public function testGetAllReturnsArray()
    {
        $developerModel = new DeveloperModel();
        $result = $developerModel->getAll(['limit' => 10]);
        $this->assertIsArray($result);
    }

    public function testGetOneReturnsObject()
    {
        $developerModel = new DeveloperModel();
        $result = $developerModel->getOne(1); // Assuming developer with ID 1 exists
        $this->assertIsObject($result);
    }
}
