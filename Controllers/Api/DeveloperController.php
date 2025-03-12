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

      $name = $this->getRequestBody('name');
      $email = $this->getRequestBody('email');
      return $this->model->add(array('name' => $name, 'email' =>  $email));
    });
  }

  public function updateAction(): void
  {
    $this->doAction($fn = function () {
      $id = $this->getQueryString('id');
      $name = $this->getQueryString('name');
      $email = $this->getQueryString('email');
      return $this->model->modify(array('id' => $id, 'name' => $name, 'email' =>  $email));
    });
  }
}