<?php
class ProjectController extends BaseController
{
  public function __construct()
  {
    parent::__construct(new ProjectModel());
  }

  public function addAction(): void
  {
    $this->doAction($fn = function () {

      $name = $this->getRequestBody('name');
      $description = $this->getRequestBody('description');
      $id_user = $this->getRequestBody('id_user');
      return $this->model->add(array('name' => $name, 'description' => $description, 'id_user' => $id_user));
    });
  }

  public function updateAction(): void
  {
    $this->doAction($fn = function () {
      $id = $this->getUriSegments()[3];
      $name = $this->getRequestBody('name');
      $description = $this->getRequestBody('description');

      return $this->model->modify(
        array(
          'id' => $id,
          'name' => $name,
          'description' => $description
        )
      );
    });
  }

  public function addProjectToUser(): void
  {
    $this->doAction($fn = function () {
      $user_id = $this->getUriSegments()[3];
      $user_ids = array($user_id);
      $project_id = $this->getUriSegments()[5];
      return $this->model->addToProject(array('user_ids' => $user_ids, 'project_id' => $project_id));
    });
  }
}