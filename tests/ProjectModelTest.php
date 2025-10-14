<?php

require_once __DIR__ . '/MemoryTestCase.php';

class ProjectModelTest extends MemoryTestCase
{
    public function testProjectModelCanBeInstantiated()
    {
        $projectModel = new ProjectModel();
        $this->assertInstanceOf(ProjectModel::class, $projectModel);
    }

    public function testGetAllReturnsArray()
    {
        $projectModel = new ProjectModel();
        $result = $projectModel->getAll(['limit' => 10]);
        $this->assertIsArray($result);
    }

    public function testGetOneReturnsObject()
    {
        $projectModel = new ProjectModel();
        $result = $projectModel->getOne(1); // Assuming project with ID 1 exists
        $this->assertIsObject($result);
    }
}
