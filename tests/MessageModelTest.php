<?php

require_once __DIR__ . '/MemoryTestCase.php';

class MessageModelTest extends MemoryTestCase
{
    public function testMessageModelCanBeInstantiated()
    {
        $messageModel = new MessageModel();
        $this->assertInstanceOf(MessageModel::class, $messageModel);
    }

    public function testGetAllReturnsArray()
    {
        $messageModel = new MessageModel();
        $result = $messageModel->getAll(['limit' => 10]);
        $this->assertIsArray($result);
    }

    public function testGetOneReturnsObject()
    {
        $messageModel = new MessageModel();
        $result = $messageModel->getOne(1); // Assuming message with ID 1 exists
        $this->assertIsObject($result);
    }
}