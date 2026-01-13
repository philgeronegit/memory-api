<?php

class UserValidator
{
    private $userModel;

    public function __construct($userModel)
    {
        $this->userModel = $userModel;
    }

    /**
     * Validate user creation data
     *
     * @param array $data
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateCreate($data)
    {
        $errors = [];

        if (empty($data['username'])) {
            $errors[] = 'Username is required';
        }

        if (empty($data['email'])) {
            $errors[] = 'Email is required';
        }

        if (empty($data['password'])) {
            $errors[] = 'Password is required';
        }

        if (empty($data['id_role'])) {
            $errors[] = 'Role is required';
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        if (!empty($data['email'])) {
            $existingUser = $this->userModel->selectOne(
                "SELECT id_user FROM user WHERE email = ?",
                ["s", $data['email']]
            );
            if (isset($existingUser->id_user)) {
                $errors[] = 'Email already exists';
            }
        }

        if (!empty($data['username']) && strlen($data['username']) < 3) {
            $errors[] = 'Username must be at least 3 characters';
        }

        if (!empty($data['password']) && strlen($data['password']) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate user update data
     *
     * @param array $data
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateUpdate($data)
    {
        $errors = [];

        if (empty($data['id'])) {
            $errors[] = 'User ID is required for update';
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        if (!empty($data['email']) && !empty($data['id'])) {
            $existingUser = $this->userModel->selectOne(
                "SELECT id_user FROM user WHERE email = ? AND id_user != ?",
                ["si", $data['email'], $data['id']]
            );
            if (isset($existingUser->id_user)) {
                $errors[] = 'Email already exists';
            }
        }

        if (!empty($data['username']) && strlen($data['username']) < 3) {
            $errors[] = 'Username must be at least 3 characters';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
