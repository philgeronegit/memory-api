<?php

class AuthorizationService
{
    private $roleModel;
    private static $instance = null;

    public function __construct()
    {
        require_once PROJECT_ROOT_PATH . "/Models/RoleModel.php";
        $this->roleModel = new RoleModel();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new AuthorizationService();
        }
        return self::$instance;
    }

    /**
     * Check if user has permission based on role and action
     *
     * @param object $user User object with role property
     * @param string $resource Resource name (e.g., 'notes', 'users')
     * @param string $action Action name (e.g., 'create', 'update', 'delete')
     * @return bool
     */
    public function can($user, $resource, $action)
    {
        if (!$user || !isset($user->role)) {
            return false;
        }

        $role = $user->role;
        $permissionKey = $action . ':' . $resource;

        if ($role === 'admin') {
            return true;
        }

        $permissions = $this->getRolePermissions($role);

        return in_array($permissionKey, $permissions);
    }

    /**
     * Get permissions for a specific role
     *
     * @param string $role
     * @return array
     */
    private function getRolePermissions($role)
    {
        $rolePermissions = [
            'admin' => [
              "create:messages",
              "view:comments",
              "create:comments",
              "update:ownComments",
              "delete:ownComments",
              "view:notes",
              "create:notes",
              "update:ownNotes",
              "delete:ownNotes",
              "view:users",
              "create:users",
              "update:users",
              "delete:users",
              "view:roles",
              "create:roles",
              "update:roles",
              "delete:roles",
              "view:permissions",
              "create:permissions",
              "update:permissions",
              "delete:permissions",
              "view:tasks",
              "create:tasks",
              "update:tasks",
              "delete:tasks"
            ],
            'projectManager' => [
              "create:messages",
              "create:users",
              "view:users",
              "view:tasks",
              "create:tasks",
              "update:tasks",
              "delete:tasks",
              "view:projects",
              "create:projects",
              "update:projects",
              "delete:projects"
            ],
            'leadDeveloper' => [
              "create:messages",
              "view:users",
              "view:comments",
              "create:comments",
              "update:ownComments",
              "view:notes",
              "create:notes",
              "update:ownNotes",
              "delete:ownNotes",
            ],
            'developer' => [
              "create:messages",
              "view:comments",
              "create:comments",
              "update:ownComments",
              "delete:ownComments",
              "view:notes",
              "create:notes",
              "update:ownNotes",
              "delete:ownNotes",
              "view:tags",
              "create:tags",
              "update:tags",
              "view:noteTags",
              "create:noteTags",
              "update:noteTags",
              "delete:noteTags",
              "view:technicalSkills",
              "create:technicalSkills",
              "update:technicalSkills",
              "delete:technicalSkills"
            ],
        ];

        return $rolePermissions[$role] ?? [];
    }

    /**
     * Check if user owns a resource
     *
     * @param object $user
     * @param int $resourceOwnerId
     * @return bool
     */
    public function owns($user, $resourceOwnerId)
    {
        if (!$user || !isset($user->id_user)) {
            return false;
        }

        return $user->id_user === $resourceOwnerId;
    }

    /**
     * Check if user can modify (update/delete) resource
     * User can modify if they own it OR have admin role
     *
     * @param object $user
     * @param int $resourceOwnerId
     * @return bool
     */
    public function canModify($user, $resourceOwnerId)
    {
        return $this->owns($user, $resourceOwnerId) ||
               (isset($user->role) && $user->role === 'admin');
    }

    /**
     * Log authorization failure
     *
     * @param string $resource
     * @param string $action
     * @param int|null $userId
     * @param string|null $role
     */
    public function logAuthorizationFailure($resource, $action, $userId, $role)
    {
        $logger = SecurityLogger::getInstance();
        $logger->logAuthorizationFailure(
            $resource,
            $action,
            $userId,
            $role
        );
    }
}
