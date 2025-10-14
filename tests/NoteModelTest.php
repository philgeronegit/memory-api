<?php

require_once __DIR__ . '/MemoryTestCase.php';

class NoteModelTest extends MemoryTestCase
{
    public function testNoteModelCanBeInstantiated()
    {
        $noteModel = new NoteModel();
        $this->assertInstanceOf(NoteModel::class, $noteModel);
    }

    public function testGetAllReturnsArray()
    {
        $noteModel = new NoteModel();
        $result = $noteModel->getAll(['limit' => 10]);
        $this->assertIsArray($result);
    }

    public function testGetOneReturnsObject()
    {
        $noteModel = new NoteModel();
        $result = $noteModel->getOne(1); // Assuming note with ID 1 exists
        $this->assertIsObject($result);
    }
}