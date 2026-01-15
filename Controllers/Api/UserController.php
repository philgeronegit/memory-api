<?php
class UserController extends BaseController
{
  private $userService;

  public function __construct()
  {
    parent::__construct(new UserModel());
    $userService = new UserService($this->model);
    $this->userService = $userService;
  }

  public function addAction(): void
  {
    $this->doAction($fn = function () {
      $data = [
        'username' => $this->getRequestBody('username'),
        'email' => $this->getRequestBody('email'),
        'avatar_url' => $this->getRequestBody('avatar_url'),
        'id_role' => $this->getRequestBody('id_role'),
        'is_admin' => $this->getRequestBody('is_admin'),
        'password' => $this->getRequestBody('password'),
      ];

      return $this->sendOutput($this->userService->createUser($data));
    });
  }

  public function updateAction(): void
  {
    $this->doAction($fn = function () {
      $id = $this->getUriSegments()[3];

      $data = [
        'username' => $this->getRequestBody('username'),
        'email' => $this->getRequestBody('email'),
        'avatar_url' => $this->getRequestBody('avatar_url'),
        'id_role' => $this->getRequestBody('id_role'),
        'is_admin' => $this->getRequestBody('is_admin'),
      ];

      return $this->sendOutput($this->userService->updateUser($id, $data));
    });
  }

  public function removeAction(): void
  {
    $this->doAction($fn = function () {
      $id = $this->getUriSegments()[3];
      return $this->userService->deleteUser($id);
    });
  }
}