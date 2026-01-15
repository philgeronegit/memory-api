<?php

abstract class BaseService
{
    protected $authorizationService;
    protected $validationService;

    /**
     * Get authenticated user from global context
     *
     * @return object|null
     */
    protected function getAuthenticatedUser()
    {
        return $GLOBALS['jwt_user_data'] ?? null;
    }

    /**
     * Get current authenticated user's ID
     *
     * @return int|null
     */
    protected function getCurrentUserId()
    {
        $user = $this->getAuthenticatedUser();
        return $user ? $user->id_user : null;
    }

    /**
     * Get current authenticated user's role
     *
     * @return string|null
     */
    protected function getCurrentUserRole()
    {
        $user = $this->getAuthenticatedUser();
        return $user ? $user->role : null;
    }

    /**
     * Throw a validation exception with appropriate HTTP context
     *
     * @param string $message
     * @throws Exception
     */
    protected function throwValidationError($message)
    {
        throw new Exception($message);
    }

    /**
     * Throw an authorization exception with appropriate HTTP context
     *
     * @param string $message
     * @throws Exception
     */
    protected function throwAuthorizationError($message)
    {
        throw new Exception($message);
    }
}
