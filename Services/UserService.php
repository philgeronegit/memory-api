<?php
require_once PROJECT_ROOT_PATH . "/Services/BaseService.php";
require_once PROJECT_ROOT_PATH . "/Services/AuthorizationService.php";
require_once PROJECT_ROOT_PATH . "/Services/UserValidator.php";

class UserService extends BaseService
{
    private $userModel;
    private $validator;

    public function __construct($userModel)
    {
        $this->userModel = $userModel;
        $this->authorizationService = AuthorizationService::getInstance();
        $this->validator = new UserValidator($userModel);
    }

    /**
     * Create a new user with business rule enforcement
     *
     * @param array $data User data
     * @return array Created user
     * @throws Exception
     */
    public function createUser($data)
    {
        $currentUser = $this->getAuthenticatedUser();

        if (!$currentUser) {
            $this->throwAuthorizationError('Authentication required');
        }

        if (!in_array($currentUser->role, ['projectManager', 'admin'])) {
            $this->authorizationService->logAuthorizationFailure(
                'users',
                'create',
                $currentUser->id_user ?? null,
                $currentUser->role ?? 'unknown'
            );
            $this->throwAuthorizationError('You do not have permission to create users');
        }

        $validation = $this->validator->validateCreate($data);
        if (!$validation['valid']) {
            $this->throwValidationError(implode(', ', $validation['errors']));
        }

        try {
            return $this->userModel->add($data);
        } catch (Exception $e) {
            throw new Exception('Failed to create user: ' . $e->getMessage());
        }
    }

    /**
     * Update a user with business rule enforcement
     *
     * @param int $userId
     * @param array $data User data
     * @return array Updated user
     * @throws Exception
     */
    public function updateUser($userId, $data)
    {
        $currentUser = $this->getAuthenticatedUser();

        if (!$currentUser) {
            $this->throwAuthorizationError('Authentication required');
        }

        if (!in_array($currentUser->role, ['projectManager', 'admin'])) {
            $this->authorizationService->logAuthorizationFailure(
                'users',
                'update',
                $currentUser->id_user ?? null,
                $currentUser->role ?? 'unknown'
            );
            $this->throwAuthorizationError('You do not have permission to update users');
        }

        $data['id'] = $userId;

        $validation = $this->validator->validateUpdate($data);
        if (!$validation['valid']) {
            $this->throwValidationError(implode(', ', $validation['errors']));
        }

        $existingUser = $this->userModel->getOne($userId);
        if (empty((array)$existingUser)) {
            $this->throwValidationError('User not found');
        }

        try {
            return $this->userModel->modify($data);
        } catch (Exception $e) {
            throw new Exception('Failed to update user: ' . $e->getMessage());
        }
    }

    /**
     * Delete a user with business rule enforcement
     *
     * @param int $userId
     * @return bool
     * @throws Exception
     */
    public function deleteUser($userId)
    {
        $currentUser = $this->getAuthenticatedUser();

        if (!$currentUser) {
            $this->throwAuthorizationError('Authentication required');
        }

        if (!in_array($currentUser->role, ['projectManager', 'admin'])) {
            $this->authorizationService->logAuthorizationFailure(
                'users',
                'delete',
                $currentUser->id_user ?? null,
                $currentUser->role ?? 'unknown'
            );
            $this->throwAuthorizationError('You do not have permission to delete users');
        }

        $existingUser = $this->userModel->getOne($userId);
        if (empty((array)$existingUser)) {
            $this->throwValidationError('User not found');
        }

        if ($userId === $currentUser->id_user) {
            $this->throwValidationError('You cannot delete yourself');
        }

        try {
            return $this->userModel->remove($userId);
        } catch (Exception $e) {
            throw new Exception('Failed to delete user: ' . $e->getMessage());
        }
    }
}
