<?php

class NoteValidator
{
    /**
     * Validate note creation data
     *
     * @param array $data
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateCreate($data)
    {
        $errors = [];

        if (empty($data['title'])) {
            $errors[] = 'Title is required';
        }

        if (empty($data['content'])) {
            $errors[] = 'Content is required';
        }

        if (empty($data['type'])) {
            $errors[] = 'Type is required';
        }

        if (empty($data['id_user'])) {
            $errors[] = 'User ID is required';
        }

        if (!empty($data['title']) && strlen($data['title']) > 255) {
            $errors[] = 'Title must not exceed 255 characters';
        }

        if (!empty($data['type']) && !in_array($data['type'], ['note', 'code', 'snippet'])) {
            $errors[] = 'Type must be one of: note, code, snippet';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate note update data
     *
     * @param array $data
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateUpdate($data)
    {
        $errors = [];

        if (empty($data['id'])) {
            $errors[] = 'Note ID is required for update';
        }

        if (empty($data['title']) && empty($data['content']) &&
            !isset($data['is_public']) && empty($data['id_project']) &&
            empty($data['id_programming_language'])) {
            $errors[] = 'At least one field must be provided for update';
        }

        if (!empty($data['title']) && strlen($data['title']) > 255) {
            $errors[] = 'Title must not exceed 255 characters';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
