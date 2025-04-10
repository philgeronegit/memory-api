<?php
class DeveloperController extends BaseController
{
  public function __construct()
  {
    parent::__construct(new DeveloperModel());
  }

  public function addAction(): void
  {
    $this->doAction($fn = function () {

      $username = $this->getRequestBody('username');
      $email = $this->getRequestBody('email');
      return $this->model->add(array('username' => $username, 'email' =>  $email));
    });
  }

  public function updateAction(): void
  {
    $this->doAction($fn = function () {
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