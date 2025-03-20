<?php
class NoteController extends BaseController
{
  public function __construct()
  {
    parent::__construct(new NoteModel());
  }

  public function addAction(): void
  {
    $this->doAction($fn = function () {
      $title = $this->getRequestBody('title');
      $content = $this->getRequestBody('content');
      $id_users = $this->getRequestBody('id_users');
      $is_public = $this->getRequestBody('is_public');
      $id_programming_language = $this->getRequestBody('id_programming_language');
      $id_project = $this->getRequestBody('id_project');
      return $this->model->add(
        array(
          'title' => $title,
          'content' => $content,
          'type' => 'text',
          'id_users' => $id_users,
          'is_public' => $is_public,
          'id_programming_language' => $id_programming_language,
          'id_project' => $id_project
        )
      );
    });
  }

  public function updateAction(): void
  {
    $this->doAction($fn = function () {
      $id = $this->getRequestBody('id');
      $title = $this->getRequestBody('title');
      $content = $this->getRequestBody('content');
      return $this->model->modify(
        array(
          'id' => $id,
          'title' => $title,
          'content' => $content
        )
      );
    });
  }
}