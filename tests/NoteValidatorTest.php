<?php

require_once __DIR__ . '/MemoryTestCase.php';

class NoteValidatorTest extends MemoryTestCase
{
    private $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new NoteValidator();
    }

    // ========== validateCreate Tests ==========

    public function testValidateCreateWithValidData()
    {
        $data = [
            'title' => 'Test Note Title',
            'content' => 'This is the note content',
            'type' => 'note',
            'id_user' => 1
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateCreateWithAllValidTypes()
    {
        $types = ['note', 'code', 'snippet'];

        foreach ($types as $type) {
            $data = [
                'title' => 'Test Title',
                'content' => 'Test Content',
                'type' => $type,
                'id_user' => 1
            ];

            $result = $this->validator->validateCreate($data);

            $this->assertTrue($result['valid'], "Failed for type: $type");
            $this->assertEmpty($result['errors'], "Failed for type: $type");
        }
    }

    public function testValidateCreateFailsWithMissingTitle()
    {
        $data = [
            'content' => 'Test Content',
            'type' => 'note',
            'id_user' => 1
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Title is required', $result['errors']);
    }

    public function testValidateCreateFailsWithEmptyTitle()
    {
        $data = [
            'title' => '',
            'content' => 'Test Content',
            'type' => 'note',
            'id_user' => 1
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Title is required', $result['errors']);
    }

    public function testValidateCreateFailsWithMissingContent()
    {
        $data = [
            'title' => 'Test Title',
            'type' => 'note',
            'id_user' => 1
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Content is required', $result['errors']);
    }

    public function testValidateCreateFailsWithEmptyContent()
    {
        $data = [
            'title' => 'Test Title',
            'content' => '',
            'type' => 'note',
            'id_user' => 1
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Content is required', $result['errors']);
    }

    public function testValidateCreateFailsWithMissingType()
    {
        $data = [
            'title' => 'Test Title',
            'content' => 'Test Content',
            'id_user' => 1
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Type is required', $result['errors']);
    }

    public function testValidateCreateFailsWithInvalidType()
    {
        $data = [
            'title' => 'Test Title',
            'content' => 'Test Content',
            'type' => 'invalid_type',
            'id_user' => 1
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Type must be one of: note, code, snippet', $result['errors']);
    }

    public function testValidateCreateFailsWithMissingUserId()
    {
        $data = [
            'title' => 'Test Title',
            'content' => 'Test Content',
            'type' => 'note'
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('User ID is required', $result['errors']);
    }

    public function testValidateCreateFailsWithTitleTooLong()
    {
        $data = [
            'title' => str_repeat('A', 256), // 256 characters
            'content' => 'Test Content',
            'type' => 'note',
            'id_user' => 1
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Title must not exceed 255 characters', $result['errors']);
    }

    public function testValidateCreateSucceedsWithMaxLengthTitle()
    {
        $data = [
            'title' => str_repeat('A', 255), // Exactly 255 characters
            'content' => 'Test Content',
            'type' => 'note',
            'id_user' => 1
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateCreateFailsWithMultipleErrors()
    {
        $data = [
            'type' => 'invalid',
            'id_user' => 1
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertCount(3, $result['errors']); // title, content, invalid type
        $this->assertContains('Title is required', $result['errors']);
        $this->assertContains('Content is required', $result['errors']);
        $this->assertContains('Type must be one of: note, code, snippet', $result['errors']);
    }

    // ========== validateUpdate Tests ==========

    public function testValidateUpdateWithValidData()
    {
        $data = [
            'id' => 1,
            'title' => 'Updated Title',
            'content' => 'Updated Content'
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateUpdateWithOnlyTitle()
    {
        $data = [
            'id' => 1,
            'title' => 'Updated Title'
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateUpdateWithOnlyContent()
    {
        $data = [
            'id' => 1,
            'content' => 'Updated Content'
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateUpdateWithIsPublic()
    {
        $data = [
            'id' => 1,
            'is_public' => true
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateUpdateWithProjectId()
    {
        $data = [
            'id' => 1,
            'id_project' => 5
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateUpdateWithProgrammingLanguageId()
    {
        $data = [
            'id' => 1,
            'id_programming_language' => 3
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateUpdateFailsWithMissingId()
    {
        $data = [
            'title' => 'Updated Title',
            'content' => 'Updated Content'
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Note ID is required for update', $result['errors']);
    }

    public function testValidateUpdateFailsWithEmptyId()
    {
        $data = [
            'id' => '',
            'title' => 'Updated Title'
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Note ID is required for update', $result['errors']);
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

    public function testValidateUpdateFailsWithTitleTooLong()
    {
        $data = [
            'id' => 1,
            'title' => str_repeat('A', 256)
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Title must not exceed 255 characters', $result['errors']);
    }

    public function testValidateUpdateSucceedsWithMaxLengthTitle()
    {
        $data = [
            'id' => 1,
            'title' => str_repeat('A', 255)
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateUpdateFailsWithMultipleErrors()
    {
        $data = [
            'title' => str_repeat('A', 256)
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertFalse($result['valid']);
        $this->assertCount(2, $result['errors']); // missing id, title too long
    }
}
