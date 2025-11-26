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
      $this->sendOutput(
        json_encode(array("error" => "Invalid credentials")),
        array('Content-Type: application/json', 'HTTP/1.1 401 Unauthorized')
      );
    });
  }

  public function updateAction(): void
  {
    $this->doAction($fn = function () {
      $id = $this->getUriSegments()[3];
      $password = $this->getRequestBody('password');
      return $this->model->modify(array(
        'id' => $id,
        'password' => $password,
      ));
    });
  }
}