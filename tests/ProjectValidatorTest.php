<?php

require_once __DIR__ . '/MemoryTestCase.php';

class ProjectValidatorTest extends MemoryTestCase
{
    private $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ProjectValidator();
    }

    // ========== validateCreate Tests ==========

    public function testValidateCreateWithValidData()
    {
        $data = [
            'name' => 'Test Project',
            'id_user' => 1,
            'description' => 'Project description'
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateCreateWithMinimalData()
    {
        $data = [
            'name' => 'Test Project',
            'id_user' => 1
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateCreateFailsWithMissingName()
    {
        $data = [
            'id_user' => 1,
            'description' => 'Description without name'
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Project name is required', $result['errors']);
    }

    public function testValidateCreateFailsWithEmptyName()
    {
        $data = [
            'name' => '',
            'id_user' => 1
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Project name is required', $result['errors']);
    }

    public function testValidateCreateFailsWithMissingUserId()
    {
        $data = [
            'name' => 'Test Project',
            'description' => 'Description'
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('User ID is required', $result['errors']);
    }

    public function testValidateCreateFailsWithEmptyUserId()
    {
        $data = [
            'name' => 'Test Project',
            'id_user' => ''
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('User ID is required', $result['errors']);
    }

    public function testValidateCreateFailsWithNameTooLong()
    {
        $data = [
            'name' => str_repeat('A', 256), // 256 characters
            'id_user' => 1
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Project name must not exceed 255 characters', $result['errors']);
    }

    public function testValidateCreateSucceedsWithMaxLengthName()
    {
        $data = [
            'name' => str_repeat('A', 255), // Exactly 255 characters
            'id_user' => 1
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateCreateFailsWithNameTooShort()
    {
        $data = [
            'name' => 'AB', // Only 2 characters
            'id_user' => 1
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Project name must be at least 3 characters', $result['errors']);
    }

    public function testValidateCreateSucceedsWithMinimumNameLength()
    {
        $data = [
            'name' => 'ABC', // Exactly 3 characters
            'id_user' => 1
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateCreateFailsWithMultipleErrors()
    {
        $data = [
            'name' => 'AB' // Too short and missing id_user
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertCount(2, $result['errors']); // Short name, missing user ID
        $this->assertContains('Project name must be at least 3 characters', $result['errors']);
        $this->assertContains('User ID is required', $result['errors']);
    }

    public function testValidateCreateWithLongDescription()
    {
        $data = [
            'name' => 'Test Project',
            'id_user' => 1,
            'description' => str_repeat('A', 1000) // Long description should be allowed
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    // ========== validateUpdate Tests ==========

    public function testValidateUpdateWithValidData()
    {
        $data = [
            'id' => 1,
            'name' => 'Updated Project Name',
            'description' => 'Updated description'
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateUpdateWithOnlyName()
    {
        $data = [
            'id' => 1,
            'name' => 'Updated Project Name'
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateUpdateWithOnlyDescription()
    {
        $data = [
            'id' => 1,
            'description' => 'Updated description'
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateUpdateFailsWithMissingId()
    {
        $data = [
            'name' => 'Updated Name',
            'description' => 'Updated description'
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Project ID is required for update', $result['errors']);
    }

    public function testValidateUpdateFailsWithEmptyId()
    {
        $data = [
            'id' => '',
            'name' => 'Updated Name'
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Project ID is required for update', $result['errors']);
    }

    public function testValidateUpdateFailsWithNoFieldsToUpdate()
    {
        $data = [
            'id' => 1
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('At least one field must be provided for update', $result['errors']);
    }

    public function testValidateUpdateFailsWithOnlyEmptyFields()
    {
        $data = [
            'id' => 1,
            'name' => '',
            'description' => ''
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('At least one field must be provided for update', $result['errors']);
    }

    public function testValidateUpdateFailsWithNameTooLong()
    {
        $data = [
            'id' => 1,
            'name' => str_repeat('A', 256)
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Project name must not exceed 255 characters', $result['errors']);
    }

    public function testValidateUpdateSucceedsWithMaxLengthName()
    {
        $data = [
            'id' => 1,
            'name' => str_repeat('A', 255)
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateUpdateFailsWithNameTooShort()
    {
        $data = [
            'id' => 1,
            'name' => 'AB'
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Project name must be at least 3 characters', $result['errors']);
    }

    public function testValidateUpdateSucceedsWithMinimumNameLength()
    {
        $data = [
            'id' => 1,
            'name' => 'ABC'
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateUpdateWithEmptyDescriptionIsValid()
    {
        $data = [
            'id' => 1,
            'name' => 'Valid Name'
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateUpdateFailsWithMultipleErrors()
    {
        $data = [
            'name' => 'AB' // Missing ID and name too short
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertFalse($result['valid']);
        $this->assertCount(2, $result['errors']); // Missing ID, short name
        $this->assertContains('Project ID is required for update', $result['errors']);
        $this->assertContains('Project name must be at least 3 characters', $result['errors']);
    }

    public function testValidateUpdateWithLongDescription()
    {
        $data = [
            'id' => 1,
            'description' => str_repeat('A', 1000) // Long description should be allowed
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateUpdateSucceedsWithAllFields()
    {
        $data = [
            'id' => 1,
            'name' => 'Complete Update',
            'description' => 'Complete description update'
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }
}
