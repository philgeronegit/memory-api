<?php
class TaskController extends BaseController
{
  public function __construct()
  {
    parent::__construct(new TaskModel());
  }

  public function addAction(): void
  {
    $this->doAction($fn = function () {

      $title = $this->getRequestBody('title');
      $description = $this->getRequestBody('description');
      $id_status = $this->getRequestBody('id_status');
      return $this->model->add(array(
        'title' => $title,
        'description' => $description,
        'id_status' => $id_status
      ));
    });
  }

  public function updateAction(): void
  {
    $this->doAction($fn = function () {
      $id = $this->getUriSegments()[3];
      $title = $this->getRequestBody('title');
      $description = $this->getRequestBody('description');
      $id_status = $this->getRequestBody('id_status');
      return $this->model->modify(
        array(
          'id' => $id,
          'title' => $title,
          'description' => $description,
          'id_status' => $id_status
        )
      );
    });
  }
}