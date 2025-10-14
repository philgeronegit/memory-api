<?php

require_once __DIR__ . '/MemoryTestCase.php';

class TaskModelTest extends MemoryTestCase
{
    public function testTaskModelCanBeInstantiated()
    {
        $taskModel = new TaskModel();
        $this->assertInstanceOf(TaskModel::class, $taskModel);
    }

    public function testGetAllReturnsArray()
    {
        $taskModel = new TaskModel();
        $result = $taskModel->getAll(['limit' => 10]);
        $this->assertIsArray($result);
    }

    public function testGetOneReturnsObject()
    {
        $taskModel = new TaskModel();
        $result = $taskModel->getOne(1); // Assuming task with ID 1 exists
        $this->assertIsObject($result);
    }
}
