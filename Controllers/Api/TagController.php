<?php
class TagController extends BaseController
{
  public function __construct()
  {
    parent::__construct(new TagModel());
  }

  public function addAction(): void
  {
    $this->doAction($fn = function () {

      $name = $this->getRequestBody('name');
      return $this->model->add(array('name' => $name));
    });
  }

  public function updateAction(): void
  {
    $this->doAction($fn = function () {
      $id = $this->getQueryString('id');
      $name = $this->getRequestBody('name');
      return $this->model->modify(
        array(
          'id' => $id,
          'name' => $name
        )
      );
    });
  }
}