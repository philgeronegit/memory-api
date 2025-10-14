<?php

require_once __DIR__ . '/MemoryTestCase.php';

class UserModelTest extends MemoryTestCase
{
    public function testUserModelCanBeInstantiated()
    {
        $userModel = new UserModel();
        $this->assertInstanceOf(UserModel::class, $userModel);
    }

    public function testGetAllReturnsArray()
    {
        $userModel = new UserModel();
        $result = $userModel->getAll(['limit' => 10]);
        $this->assertIsArray($result);
    }

    public function testGetOneReturnsObject()
    {
        $userModel = new UserModel();
        $result = $userModel->getOne(1); // Assuming user with ID 1 exists
        $this->assertIsObject($result);
    }

    public function testAddAndRemoveUser()
    {
        $userModel = new UserModel();
        $username = 'testuser_' . uniqid();
        $userData = [
            'username' => $username,
            'email' => $username . '@example.com',
            'avatar_url' => 'http://example.com/avatar.jpg',
            'id_role' => 1,
            'is_admin' => 0,
            'password' => 'password123'
        ];

        $addedUser = $userModel->add($userData);
        $this->assertIsObject($addedUser);
        $this->assertObjectHasProperty('id_user', $addedUser);

        $userId = $addedUser->id_user;
        print_r("Added user ID: $userId\n");

        // wait for the trigger to add a row in messages table
        // sleep(1);

        // remove all messages for this user in table messages
        $messageModel = new MessageModel();
        $messages = $messageModel->getAll(['id' => $userId, 'limit' => 10]);
        foreach ($messages as $message) {
            print_r("Removing message ID: " . $message['id_message'] . "\n");
            $messageModel->remove($message['id_message']);
        }

        $removeResult = $userModel->remove($userId);
        $this->assertTrue($removeResult);

        $retrievedUser = $userModel->getOne($userId);
        // assert empty object
        $this->assertEmpty((array)$retrievedUser);
    }
}