<?php

class ProjectValidator
{
    /**
     * Validate project creation data
     *
     * @param array $data
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateCreate($data)
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors[] = 'Project name is required';
        }

        if (empty($data['id_user'])) {
            $errors[] = 'User ID is required';
        }

        if (!empty($data['name']) && strlen($data['name']) > 255) {
            $errors[] = 'Project name must not exceed 255 characters';
        }

        if (!empty($data['name']) && strlen($data['name']) < 3) {
            $errors[] = 'Project name must be at least 3 characters';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate project update data
     *
     * @param array $data
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateUpdate($data)
    {
        $errors = [];

        if (empty($data['id'])) {
            $errors[] = 'Project ID is required for update';
        }

        if (empty($data['name']) && empty($data['description'])) {
            $errors[] = 'At least one field must be provided for update';
        }

        if (!empty($data['name']) && strlen($data['name']) > 255) {
            $errors[] = 'Project name must not exceed 255 characters';
        }

        if (!empty($data['name']) && strlen($data['name']) < 3) {
            $errors[] = 'Project name must be at least 3 characters';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
