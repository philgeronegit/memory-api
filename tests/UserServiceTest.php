<?php

require_once __DIR__ . '/MemoryTestCase.php';

class UserServiceTest extends MemoryTestCase
{
    private $userService;
    private $userModelMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userModelMock = $this->createMock(UserModel::class);
        $this->userService = new UserService($this->userModelMock);
    }

    public function testCreateUserAsProjectManager()
    {
        $userData = (object) [
            'id_user' => 1,
            'username' => 'manager',
            'role' => 'projectManager'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $newUserData = [
            'username' => 'newuser',
            'email' => 'new@example.com',
            'password' => 'password123',
            'id_role' => 2,
            'avatar_url' => null,
            'is_admin' => false
        ];

        $this->userModelMock
            ->method('selectOne')
            ->willReturn((object)[]);

        $this->userModelMock
            ->expects($this->once())
            ->method('add')
            ->with($newUserData)
            ->willReturn(array_merge($newUserData, ['id_user' => 2]));

        $result = $this->userService->createUser($newUserData);

        $this->assertIsArray($result);
        $this->assertEquals(2, $result['id_user']);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testCreateUserAsAdmin()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'admin'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $newUserData = [
            'username' => 'newuser',
            'email' => 'new@example.com',
            'password' => 'password123',
            'id_role' => 2
        ];

        $this->userModelMock
            ->method('selectOne')
            ->willReturn((object)[]);

        $this->userModelMock
            ->expects($this->once())
            ->method('add')
            ->willReturn(array_merge($newUserData, ['id_user' => 2]));

        $result = $this->userService->createUser($newUserData);

        $this->assertIsArray($result);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testCreateUserFailsAsDeveloper()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'developer'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $newUserData = [
            'username' => 'newuser',
            'email' => 'new@example.com',
            'password' => 'password123',
            'id_role' => 2
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You do not have permission to create users');

        $this->userService->createUser($newUserData);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testCreateUserFailsWithMissingUsername()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'admin'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $newUserData = [
            'email' => 'new@example.com',
            'password' => 'password123',
            'id_role' => 2
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Username is required');

        $this->userService->createUser($newUserData);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testCreateUserFailsWithMissingEmail()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'admin'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $newUserData = [
            'username' => 'newuser',
            'password' => 'password123',
            'id_role' => 2
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Email is required');

        $this->userService->createUser($newUserData);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testCreateUserFailsWithInvalidEmail()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'admin'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $newUserData = [
            'username' => 'newuser',
            'email' => 'invalid-email',
            'password' => 'password123',
            'id_role' => 2
        ];

        $this->userModelMock
            ->method('selectOne')
            ->willReturn((object)[]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid email format');

        $this->userService->createUser($newUserData);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testCreateUserFailsWithDuplicateEmail()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'admin'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $existingUser = (object) ['id_user' => 5, 'email' => 'existing@example.com'];

        $this->userModelMock
            ->expects($this->once())
            ->method('selectOne')
            ->willReturn($existingUser);

        $newUserData = [
            'username' => 'newuser',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'id_role' => 2
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Email already exists');

        $this->userService->createUser($newUserData);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testCreateUserFailsWithShortPassword()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'admin'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $this->userModelMock
            ->method('selectOne')
            ->willReturn((object)[]);

        $newUserData = [
            'username' => 'newuser',
            'email' => 'new@example.com',
            'password' => 'short',
            'id_role' => 2
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Password must be at least 8 characters');

        $this->userService->createUser($newUserData);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testUpdateUserSuccess()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'admin'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $existingUser = (object) [
            'id_user' => 2,
            'username' => 'oldname',
            'email' => 'old@example.com'
        ];

        $this->userModelMock
            ->expects($this->once())
            ->method('getOne')
            ->with(2)
            ->willReturn($existingUser);

        $this->userModelMock
            ->method('selectOne')
            ->willReturn((object)[]);

        $updateData = [
            'username' => 'newname',
            'email' => 'new@example.com'
        ];

        $this->userModelMock
            ->expects($this->once())
            ->method('modify')
            ->willReturn(array_merge((array)$existingUser, $updateData));

        $result = $this->userService->updateUser(2, $updateData);

        $this->assertIsArray($result);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testUpdateUserFailsWhenUserNotFound()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'admin'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $this->userModelMock
            ->expects($this->once())
            ->method('getOne')
            ->with(999)
            ->willReturn((object)[]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('User not found');

        $this->userService->updateUser(999, ['username' => 'newname']);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testDeleteUserSuccess()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'admin'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $existingUser = (object) [
            'id_user' => 2,
            'username' => 'userToDelete'
        ];

        $this->userModelMock
            ->expects($this->once())
            ->method('getOne')
            ->with(2)
            ->willReturn($existingUser);

        $this->userModelMock
            ->expects($this->once())
            ->method('remove')
            ->with(2)
            ->willReturn(true);

        $result = $this->userService->deleteUser(2);

        $this->assertTrue($result);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testDeleteUserFailsWhenDeletingSelf()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'admin'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $existingUser = (object) [
            'id_user' => 1,
            'username' => 'self'
        ];

        $this->userModelMock
            ->expects($this->once())
            ->method('getOne')
            ->with(1)
            ->willReturn($existingUser);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You cannot delete yourself');

        $this->userService->deleteUser(1);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testDeleteUserFailsAsDeveloper()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'developer'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You do not have permission to delete users');

        $this->userService->deleteUser(2);

        unset($GLOBALS['jwt_user_data']);
    }
}
