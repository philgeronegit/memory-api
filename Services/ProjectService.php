<?php
require_once PROJECT_ROOT_PATH . "/Services/BaseService.php";
require_once PROJECT_ROOT_PATH . "/Services/AuthorizationService.php";
require_once PROJECT_ROOT_PATH . "/Services/ProjectValidator.php";

class ProjectService extends BaseService
{
    private $projectModel;
    private $validator;

    public function __construct($projectModel)
    {
        $this->projectModel = $projectModel;
        $this->authorizationService = AuthorizationService::getInstance();
        $this->validator = new ProjectValidator();
    }

    /**
     * Create a new project with business rule enforcement
     *
     * @param array $data Project data
     * @return array Created project
     * @throws Exception
     */
    public function createProject($data)
    {
        $user = $this->getAuthenticatedUser();

        if (!$user) {
            $this->throwAuthorizationError('Authentication required');
        }

        $validation = $this->validator->validateCreate($data);
        if (!$validation['valid']) {
            $this->throwValidationError(implode(', ', $validation['errors']));
        }

        if (!$this->authorizationService->can($user, 'projects', 'create')) {
            $this->authorizationService->logAuthorizationFailure(
                'projects',
                'create',
                $user->id_user ?? null,
                $user->role ?? 'unknown'
            );
            $this->throwAuthorizationError('You do not have permission to create projects');
        }

        if ($data['id_user'] != $user->id_user && $user->role !== 'admin') {
            $this->throwAuthorizationError('You can only create projects for yourself');
        }

        try {
            return $this->projectModel->add($data);
        } catch (Exception $e) {
            throw new Exception('Failed to create project: ' . $e->getMessage());
        }
    }

    /**
     * Update a project with business rule enforcement
     *
     * @param int $projectId
     * @param array $data Project data
     * @return array Updated project
     * @throws Exception
     */
    public function updateProject($projectId, $data)
    {
        $user = $this->getAuthenticatedUser();

        if (!$user) {
            $this->throwAuthorizationError('Authentication required');
        }

        $data['id'] = $projectId;

        $validation = $this->validator->validateUpdate($data);
        if (!$validation['valid']) {
            $this->throwValidationError(implode(', ', $validation['errors']));
        }

        $existingProject = $this->projectModel->getOne($projectId);
        if (empty((array)$existingProject)) {
            $this->throwValidationError('Project not found');
        }

        $canUpdate = $this->authorizationService->canModify($user, $existingProject->id_user);

        if (!$canUpdate) {
            $this->authorizationService->logAuthorizationFailure(
                'projects',
                'update',
                $user->id_user ?? null,
                $user->role ?? 'unknown'
            );
            $this->throwAuthorizationError('You do not have permission to update this project');
        }

        try {
            return $this->projectModel->modify($data);
        } catch (Exception $e) {
            throw new Exception('Failed to update project: ' . $e->getMessage());
        }
    }

    /**
     * Delete a project with business rule enforcement
     *
     * @param int $projectId
     * @return bool
     * @throws Exception
     */
    public function deleteProject($projectId)
    {
        $user = $this->getAuthenticatedUser();

        if (!$user) {
            $this->throwAuthorizationError('Authentication required');
        }

        $existingProject = $this->projectModel->getOne($projectId);
        if (empty((array)$existingProject)) {
            $this->throwValidationError('Project not found');
        }

        $canDelete = $this->authorizationService->canModify($user, $existingProject->id_user);

        if (!$canDelete) {
            $this->authorizationService->logAuthorizationFailure(
                'projects',
                'delete',
                $user->id_user ?? null,
                $user->role ?? 'unknown'
            );
            $this->throwAuthorizationError('You do not have permission to delete this project');
        }

        try {
            return $this->projectModel->remove($projectId);
        } catch (Exception $e) {
            throw new Exception('Failed to delete project: ' . $e->getMessage());
        }
    }

    /**
     * Add user(s) to a project
     *
     * @param array $data User IDs and project ID
     * @return mixed
     * @throws Exception
     */
    public function addUsersToProject($data)
    {
        $user = $this->getAuthenticatedUser();

        if (!$user) {
            $this->throwAuthorizationError('Authentication required');
        }

        if (empty($data['user_ids']) || empty($data['project_id'])) {
            $this->throwValidationError('User IDs and Project ID are required');
        }

        $project = $this->projectModel->getOne($data['project_id']);
        if (empty((array)$project)) {
            $this->throwValidationError('Project not found');
        }

        $canManage = $this->authorizationService->canModify($user, $project->id_user);

        if (!$canManage) {
            $this->authorizationService->logAuthorizationFailure(
                'projects',
                'manage_users',
                $user->id_user ?? null,
                $user->role ?? 'unknown'
            );
            $this->throwAuthorizationError('You do not have permission to manage project users');
        }

        try {
            return $this->projectModel->addToProject($data);
        } catch (Exception $e) {
            throw new Exception('Failed to add users to project: ' . $e->getMessage());
        }
    }

    /**
     * Remove user(s) from a project
     *
     * @param array $data User IDs and project ID
     * @return mixed
     * @throws Exception
     */
    public function removeUsersFromProject($data)
    {
        $user = $this->getAuthenticatedUser();

        if (!$user) {
            $this->throwAuthorizationError('Authentication required');
        }

        if (empty($data['user_ids']) || empty($data['project_id'])) {
            $this->throwValidationError('User IDs and Project ID are required');
        }

        $project = $this->projectModel->getOne($data['project_id']);
        if (empty((array)$project)) {
            $this->throwValidationError('Project not found');
        }

        $canManage = $this->authorizationService->canModify($user, $project->id_user);

        if (!$canManage) {
            $this->authorizationService->logAuthorizationFailure(
                'projects',
                'manage_users',
                $user->id_user ?? null,
                $user->role ?? 'unknown'
            );
            $this->throwAuthorizationError('You do not have permission to manage project users');
        }

        try {
            return $this->projectModel->deleteFromProject($data);
        } catch (Exception $e) {
            throw new Exception('Failed to remove users from project: ' . $e->getMessage());
        }
    }
}
