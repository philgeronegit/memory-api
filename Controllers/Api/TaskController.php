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
      $id_project = $this->getRequestBody('id_project');
      $id_executive = $this->getRequestBody('id_executive');
      $id_developer = $this->getRequestBody('id_developer');
      $priority = $this->getRequestBody('priority') ?? "low";
      return $this->model->add(array(
        'title' => $title,
        'description' => $description,
        'id_status' => $id_status,
        'id_project' => $id_project,
        'id_executive' => $id_executive,
        'id_developer' => $id_developer,
        'priority' => $priority
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
      $priority = $this->getRequestBody('priority');
      $due_at = $this->getRequestBody('due_at');
      $done_at = $this->getRequestBody('done_at');
      return $this->model->modify(
        array(
          'id' => $id,
          'title' => $title,
          'description' => $description,
          'id_status' => $id_status,
          'priority' => $priority,
          'due_at' => $due_at,
          'done_at' => $done_at
        )
      );
    });
  }

  public function reorderTasks(): void
  {
    $this->doAction($fn = function () {
      $tasks = $this->getRequestBody('tasks');
      return $this->model->reorderTasks(
        array(
          'tasks' => $tasks
        )
      );
    });
  }
}