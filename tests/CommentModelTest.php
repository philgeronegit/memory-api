<?php

require_once __DIR__ . '/MemoryTestCase.php';

class CommentModelTest extends MemoryTestCase
{
    public function testCommentModelCanBeInstantiated()
    {
        $commentModel = new CommentModel();
        $this->assertInstanceOf(CommentModel::class, $commentModel);
    }

    public function testGetAllReturnsArray()
    {
        $commentModel = new CommentModel();
        $result = $commentModel->getAll(['limit' => 10]);
        $this->assertIsArray($result);
    }

    public function testGetOneReturnsObject()
    {
        $commentModel = new CommentModel();
        $result = $commentModel->getOne(1); // Assuming comment with ID 1 exists
        $this->assertIsObject($result);
    }
}
