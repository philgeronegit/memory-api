<?php
class ProjectController extends BaseController
{
  private $projectService;

  public function __construct()
  {
    parent::__construct(new ProjectModel());
    $projectService = new ProjectService($this->model);
    $this->projectService = $projectService;
  }

  public function addAction(): void
  {
    $this->doAction($fn = function () {
      $data = [
        'name' => $this->getRequestBody('name'),
        'description' => $this->getRequestBody('description'),
        'id_user' => $this->getRequestBody('id_user'),
      ];

      return $this->sendOutput($this->projectService->createProject($data));
    });
  }

  public function updateAction(): void
  {
    $this->doAction($fn = function () {
      $id = $this->getUriSegments()[3];

      $data = [
        'name' => $this->getRequestBody('name'),
        'description' => $this->getRequestBody('description'),
      ];

      return $this->sendOutput($this->projectService->updateProject($id, $data));
    });
  }

  public function removeAction(): void
  {
    $this->doAction($fn = function () {
      $id = $this->getUriSegments()[3];
      return $this->projectService->deleteProject($id);
    });
  }

  public function addProjectToUser(): void
  {
    $this->doAction($fn = function () {
      $user_id = $this->getUriSegments()[3];
      $project_id = $this->getUriSegments()[5];

      $data = [
        'user_ids' => [$user_id],
        'project_id' => $project_id,
      ];

      return $this->sendOutput($this->projectService->addUsersToProject($data));
    });
  }

  public function removeProjectFromUser(): void
  {
    $this->doAction($fn = function () {
      $user_id = $this->getUriSegments()[3];
      $project_id = $this->getUriSegments()[5];

      $data = [
        'user_ids' => [$user_id],
        'project_id' => $project_id,
      ];

      return $this->sendOutput($this->projectService->removeUsersFromProject($data));
    });
  }
}