<?php

require_once __DIR__ . '/MemoryTestCase.php';

class UserValidatorTest extends MemoryTestCase
{
    private $validator;
    private $userModelMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userModelMock = $this->createMock(UserModel::class);
        $this->validator = new UserValidator($this->userModelMock);
    }

    // ========== validateCreate Tests ==========

    public function testValidateCreateWithValidData()
    {
        $this->userModelMock
            ->method('selectOne')
            ->willReturn((object)[]); // No existing user (returns empty object)

        $data = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'id_role' => 2
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateCreateFailsWithMissingUsername()
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'id_role' => 2
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Username is required', $result['errors']);
    }

    public function testValidateCreateFailsWithEmptyUsername()
    {
        $data = [
            'username' => '',
            'email' => 'test@example.com',
            'password' => 'password123',
            'id_role' => 2
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Username is required', $result['errors']);
    }

    public function testValidateCreateFailsWithMissingEmail()
    {
        $data = [
            'username' => 'testuser',
            'password' => 'password123',
            'id_role' => 2
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Email is required', $result['errors']);
    }

    public function testValidateCreateFailsWithEmptyEmail()
    {
        $data = [
            'username' => 'testuser',
            'email' => '',
            'password' => 'password123',
            'id_role' => 2
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Email is required', $result['errors']);
    }

    public function testValidateCreateFailsWithMissingPassword()
    {
        $data = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'id_role' => 2
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Password is required', $result['errors']);
    }

    public function testValidateCreateFailsWithEmptyPassword()
    {
        $data = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => '',
            'id_role' => 2
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Password is required', $result['errors']);
    }

    public function testValidateCreateFailsWithMissingRole()
    {
        $data = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Role is required', $result['errors']);
    }

    public function testValidateCreateFailsWithInvalidEmailFormat()
    {
        $this->userModelMock
            ->method('selectOne')
            ->willReturn((object)[]);

        $data = [
            'username' => 'testuser',
            'email' => 'invalid-email',
            'password' => 'password123',
            'id_role' => 2
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Invalid email format', $result['errors']);
    }

    public function testValidateCreateWithVariousInvalidEmailFormats()
    {
        $invalidEmails = [
            'plaintext',
            '@example.com',
            'user@',
            'user @example.com',
            'user@example',
            'user..name@example.com'
        ];

        foreach ($invalidEmails as $invalidEmail) {
            $this->userModelMock
                ->method('selectOne')
                ->willReturn((object)[]);

            $data = [
                'username' => 'testuser',
                'email' => $invalidEmail,
                'password' => 'password123',
                'id_role' => 2
            ];

            $result = $this->validator->validateCreate($data);

            $this->assertFalse($result['valid'], "Failed to invalidate: $invalidEmail");
            $this->assertContains('Invalid email format', $result['errors'], "Failed for: $invalidEmail");
        }
    }

    public function testValidateCreateFailsWithDuplicateEmail()
    {
        $existingUser = (object) ['id_user' => 5, 'email' => 'existing@example.com'];

        $this->userModelMock
            ->expects($this->once())
            ->method('selectOne')
            ->with(
                "SELECT id_user FROM user WHERE email = ?",
                ["s", "existing@example.com"]
            )
            ->willReturn($existingUser);

        $data = [
            'username' => 'newuser',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'id_role' => 2
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Email already exists', $result['errors']);
    }

    public function testValidateCreateFailsWithShortUsername()
    {
        $this->userModelMock
            ->method('selectOne')
            ->willReturn((object)[]);

        $data = [
            'username' => 'ab', // Only 2 characters
            'email' => 'test@example.com',
            'password' => 'password123',
            'id_role' => 2
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Username must be at least 3 characters', $result['errors']);
    }

    public function testValidateCreateSucceedsWithMinimumUsernameLength()
    {
        $this->userModelMock
            ->method('selectOne')
            ->willReturn((object)[]);

        $data = [
            'username' => 'abc', // Exactly 3 characters
            'email' => 'test@example.com',
            'password' => 'password123',
            'id_role' => 2
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateCreateFailsWithShortPassword()
    {
        $this->userModelMock
            ->method('selectOne')
            ->willReturn((object)[]);

        $data = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'short', // Only 5 characters
            'id_role' => 2
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Password must be at least 8 characters', $result['errors']);
    }

    public function testValidateCreateSucceedsWithMinimumPasswordLength()
    {
        $this->userModelMock
            ->method('selectOne')
            ->willReturn((object)[]);

        $data = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password', // Exactly 8 characters
            'id_role' => 2
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateCreateFailsWithMultipleErrors()
    {
        $data = [
            'username' => 'ab',
            'password' => 'short'
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertGreaterThanOrEqual(4, count($result['errors'])); // Missing email, role, short username, short password
    }

    // ========== validateUpdate Tests ==========

    public function testValidateUpdateWithValidData()
    {
        $this->userModelMock
            ->method('selectOne')
            ->willReturn((object)[]);

        $data = [
            'id' => 1,
            'username' => 'updateduser',
            'email' => 'updated@example.com'
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateUpdateFailsWithMissingId()
    {
        $data = [
            'username' => 'updateduser',
            'email' => 'updated@example.com'
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('User ID is required for update', $result['errors']);
    }

    public function testValidateUpdateFailsWithInvalidEmailFormat()
    {
        $data = [
            'id' => 1,
            'email' => 'invalid-email'
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Invalid email format', $result['errors']);
    }

    public function testValidateUpdateFailsWithDuplicateEmailForDifferentUser()
    {
        $existingUser = (object) ['id_user' => 5, 'email' => 'existing@example.com'];

        $this->userModelMock
            ->expects($this->once())
            ->method('selectOne')
            ->with(
                "SELECT id_user FROM user WHERE email = ? AND id_user != ?",
                ["si", "existing@example.com", 1]
            )
            ->willReturn($existingUser);

        $data = [
            'id' => 1,
            'email' => 'existing@example.com'
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Email already exists', $result['errors']);
    }

    public function testValidateUpdateSucceedsWhenEmailBelongsToSameUser()
    {
        $this->userModelMock
            ->expects($this->once())
            ->method('selectOne')
            ->with(
                "SELECT id_user FROM user WHERE email = ? AND id_user != ?",
                ["si", "myown@example.com", 1]
            )
            ->willReturn((object)[]); // No other user has this email

        $data = [
            'id' => 1,
            'email' => 'myown@example.com'
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateUpdateFailsWithShortUsername()
    {
        $data = [
            'id' => 1,
            'username' => 'ab'
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertFalse($result['valid']);
        $this->assertContains('Username must be at least 3 characters', $result['errors']);
    }

    public function testValidateUpdateSucceedsWithMinimumUsernameLength()
    {
        $data = [
            'id' => 1,
            'username' => 'abc'
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateUpdateSucceedsWithOnlyUsername()
    {
        $data = [
            'id' => 1,
            'username' => 'newusername'
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateUpdateSucceedsWithOnlyEmail()
    {
        $this->userModelMock
            ->method('selectOne')
            ->willReturn((object)[]);

        $data = [
            'id' => 1,
            'email' => 'new@example.com'
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateUpdateWithMultipleErrors()
    {
        $data = [
            'username' => 'ab',
            'email' => 'invalid'
        ];

        $result = $this->validator->validateUpdate($data);

        $this->assertFalse($result['valid']);
        $this->assertGreaterThanOrEqual(3, count($result['errors'])); // Missing id, short username, invalid email
    }
}
