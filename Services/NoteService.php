<?php
require_once PROJECT_ROOT_PATH . "/Services/BaseService.php";
require_once PROJECT_ROOT_PATH . "/Services/AuthorizationService.php";
require_once PROJECT_ROOT_PATH . "/Services/NoteValidator.php";

class NoteService extends BaseService
{
    private $noteModel;
    private $validator;

    public function __construct($noteModel)
    {
        $this->noteModel = $noteModel;
        $this->authorizationService = AuthorizationService::getInstance();
        $this->validator = new NoteValidator();
    }

    /**
     * Create a new note with business rule enforcement
     *
     * @param array $data Note data
     * @return array Created note
     * @throws Exception
     */
    public function createNote($data)
    {
        $user = $this->getAuthenticatedUser();

        if (!$user) {
            $this->throwAuthorizationError('Authentication required');
        }

        $validation = $this->validator->validateCreate($data);
        if (!$validation['valid']) {
            $this->throwValidationError(implode(', ', $validation['errors']));
        }

        if (!$this->authorizationService->can($user, 'notes', 'create')) {
            $this->authorizationService->logAuthorizationFailure(
                'notes',
                'create',
                $user->id_user ?? null,
                $user->role ?? 'unknown'
            );
            $this->throwAuthorizationError('You do not have permission to create notes');
        }

        if ($data['id_user'] != $user->id_user && $user->role !== 'admin') {
            $this->throwAuthorizationError('You can only create notes for yourself');
        }

        try {
            return $this->noteModel->add($data);
        } catch (Exception $e) {
            throw new Exception('Failed to create note: ' . $e->getMessage());
        }
    }

    /**
     * Update a note with business rule enforcement
     *
     * @param int $noteId
     * @param array $data Note data
     * @return array Updated note
     * @throws Exception
     */
    public function updateNote($noteId, $data)
    {
        $user = $this->getAuthenticatedUser();

        if (!$user) {
            $this->throwAuthorizationError('Authentication required');
        }

        $data['id'] = $noteId;

        $validation = $this->validator->validateUpdate($data);
        if (!$validation['valid']) {
            $this->throwValidationError(implode(', ', $validation['errors']));
        }

        $existingNote = $this->noteModel->getOne($noteId);
        if (empty((array)$existingNote)) {
            $this->throwValidationError('Note not found');
        }

        $canUpdate = $this->authorizationService->canModify($user, $existingNote->id_user);

        if (!$canUpdate) {
            $this->authorizationService->logAuthorizationFailure(
                'notes',
                'update',
                $user->id_user ?? null,
                $user->role ?? 'unknown'
            );
            $this->throwAuthorizationError('You do not have permission to update this note');
        }

        try {
            return $this->noteModel->modify($data);
        } catch (Exception $e) {
            throw new Exception('Failed to update note: ' . $e->getMessage());
        }
    }

    /**
     * Delete a note with business rule enforcement
     *
     * @param int $noteId
     * @return bool
     * @throws Exception
     */
    public function deleteNote($noteId)
    {
        $user = $this->getAuthenticatedUser();

        if (!$user) {
            $this->throwAuthorizationError('Authentication required');
        }

        $existingNote = $this->noteModel->getOne($noteId);
        if (empty((array)$existingNote)) {
            $this->throwValidationError('Note not found');
        }

        $canDelete = $this->authorizationService->canModify($user, $existingNote->id_user);

        if (!$canDelete) {
            $this->authorizationService->logAuthorizationFailure(
                'notes',
                'delete',
                $user->id_user ?? null,
                $user->role ?? 'unknown'
            );
            $this->throwAuthorizationError('You do not have permission to delete this note');
        }

        try {
            return $this->noteModel->remove($noteId);
        } catch (Exception $e) {
            throw new Exception('Failed to delete note: ' . $e->getMessage());
        }
    }

    /**
     * Share a note with another user
     *
     * @param array $data Sharing data (id_item, id_user)
     * @return mixed
     * @throws Exception
     */
    public function shareNote($data)
    {
        $user = $this->getAuthenticatedUser();

        if (!$user) {
            $this->throwAuthorizationError('Authentication required');
        }

        if (empty($data['id_item']) || empty($data['id_user'])) {
            $this->throwValidationError('Item ID and User ID are required for sharing');
        }

        $note = $this->noteModel->getOne($data['id_item']);
        if (empty((array)$note)) {
            $this->throwValidationError('Note not found');
        }

        $canShare = $this->authorizationService->canModify($user, $note->id_user);

        if (!$canShare) {
            $this->authorizationService->logAuthorizationFailure(
                'notes',
                'share',
                $user->id_user ?? null,
                $user->role ?? 'unknown'
            );
            $this->throwAuthorizationError('You do not have permission to share this note');
        }

        try {
            return $this->noteModel->shareNoteWithUser($data);
        } catch (Exception $e) {
            throw new Exception('Failed to share note: ' . $e->getMessage());
        }
    }
}
