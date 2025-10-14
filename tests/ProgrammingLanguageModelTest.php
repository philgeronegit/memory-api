<?php

require_once __DIR__ . '/MemoryTestCase.php';

class ProgrammingLanguageModelTest extends MemoryTestCase
{
    public function testProgrammingLanguageModelCanBeInstantiated()
    {
        $programmingLanguageModel = new ProgrammingLanguageModel();
        $this->assertInstanceOf(ProgrammingLanguageModel::class, $programmingLanguageModel);
    }

    public function testGetAllReturnsArray()
    {
        $programmingLanguageModel = new ProgrammingLanguageModel();
        $result = $programmingLanguageModel->getAll(['limit' => 10]);
        $this->assertIsArray($result);
    }

    public function testGetOneReturnsObject()
    {
        $programmingLanguageModel = new ProgrammingLanguageModel();
        $result = $programmingLanguageModel->getOne(1); // Assuming programming language with ID 1 exists
        $this->assertIsObject($result);
    }
}
