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
          $this->sendOutput('Unauthorized to update this user', array('HTTP/1.1 403 Forbidden'));
          return;
      }

      $username = $this->getRequestBody('username');
      $email = $this->getRequestBody('email');
      $avatar_url = $this->getRequestBody('avatar_url');
      $id_role = $this->getRequestBody('id_role');
      $is_admin = $this->getRequestBody('is_admin');
      $password = $this->getRequestBody('password');
      // Check if email already exists to prevent duplicates
      $query = <<<SQL
        SELECT id_user
        FROM user
        WHERE email = ?
      SQL;
      $existingUser = $this->model->selectOne($query, ["s", $email]);
      if ($existingUser) {
        $this->sendOutput('', array('HTTP/1.1 409 Email already exists'));
        return;
      }
      return $this->model->add(array(
        'username' => $username,
        'email' =>  $email,
        'avatar_url' => $avatar_url,
        'id_role' => $id_role,
        'is_admin' => $is_admin,
        'password' => $password
      ));
    });
  }

  public function updateAction(): void
  {
    $this->doAction($fn = function () {
      if (!$this->hasUserModificationPermission()) {
          $this->sendOutput('Unauthorized to update this user', array('HTTP/1.1 403 Forbidden'));
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