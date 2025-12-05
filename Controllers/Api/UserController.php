<?php
class UserController extends BaseController
{
  public function __construct()
  {
    parent::__construct(new UserModel());
  }

  public function addAction(): void
  {
    $this->doAction($fn = function () {
      if (!$this->hasUserModificationPermission()) {
          $this->sendOutput('Unauthorized to add this user', array('HTTP/1.1 403 Forbidden'));
          return;
      }

      $username = $this->getRequestBody('username');
      $email = $this->getRequestBody('email');
      $avatar_url = $this->getRequestBody('avatar_url');
      $id_role = $this->getRequestBody('id_role');
      $is_admin = $this->getRequestBody('is_admin');
      $password = $this->getRequestBody('password');

      $paramsArray = array(
        'username' => $username,
        'email' =>  $email,
        'avatar_url' => $avatar_url,
        'id_role' => $id_role,
        'is_admin' => $is_admin,
        'password' => $password
      );
      $validationErrors = $this->model->validate($paramsArray);
      if ($validationErrors['hasErrors']) {
          $this->sendOutput(['error' => $validationErrors['error']], $validationErrors['httpHeader']);
          return;
      }
      return $this->model->add($paramsArray);
    });
  }

  public function updateAction(): void
  {
    $this->doAction($fn = function () {
      if (!$this->hasUserModificationPermission()) {
          $this->sendOutput(['error' => 'Unauthorized to update this user'], array('HTTP/1.1 403 Forbidden'));
          return;
      }

      $id = $this->getUriSegments()[3];
      $username = $this->getRequestBody('username');
      $email = $this->getRequestBody('email');
      $avatar_url = $this->getRequestBody('avatar_url');
      $id_role = $this->getRequestBody('id_role');
      $is_admin = $this->getRequestBody('is_admin');
      return $this->model->modify(array(
        'id' => $id,
        'username' => $username,
        'email' =>  $email,
        'avatar_url' => $avatar_url,
        'id_role' => $id_role,
        'is_admin' => $is_admin
      ));
    });
  }
}