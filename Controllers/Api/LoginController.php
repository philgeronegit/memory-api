<?php
class LoginController extends BaseController
{
  public function __construct()
  {
    parent::__construct(new LoginModel());
  }

  public function addAction(): void
  {
    $this->doAction($fn = function () {

      $username = $this->getRequestBody('username');
      $password = $this->getRequestBody('password');
      $return = $this->model->add(array('username' => $username, 'password' => $password));
      if (isset($return->id_user)) {
        return $return;
      }
      return array('error' => 'Invalid username or password');
    });
  }
}