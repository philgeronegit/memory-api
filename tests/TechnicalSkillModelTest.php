<?php

require_once __DIR__ . '/MemoryTestCase.php';

class TechnicalSkillModelTest extends MemoryTestCase
{
    public function testTechnicalSkillModelCanBeInstantiated()
    {
        $technicalSkillModel = new TechnicalSkillModel();
        $this->assertInstanceOf(TechnicalSkillModel::class, $technicalSkillModel);
    }

    public function testGetAllReturnsArray()
    {
        $technicalSkillModel = new TechnicalSkillModel();
        $result = $technicalSkillModel->getAll(['limit' => 10]);
        $this->assertIsArray($result);
    }

    public function testGetOneReturnsObject()
    {
        $technicalSkillModel = new TechnicalSkillModel();
        $result = $technicalSkillModel->getOne(1); // Assuming technical skill with ID 1 exists
        $this->assertIsObject($result);
    }
}
