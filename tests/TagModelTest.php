<?php

require_once __DIR__ . '/MemoryTestCase.php';

class TagModelTest extends MemoryTestCase
{
    public function testTagModelCanBeInstantiated()
    {
        $tagModel = new TagModel();
        $this->assertInstanceOf(TagModel::class, $tagModel);
    }

    public function testGetAllReturnsArray()
    {
        $tagModel = new TagModel();
        $result = $tagModel->getAll(['limit' => 10]);
        $this->assertIsArray($result);
    }

    public function testGetOneReturnsObject()
    {
        $tagModel = new TagModel();
        $result = $tagModel->getOne(1); // Assuming tag with ID 1 exists
        $this->assertIsObject($result);
    }
}
