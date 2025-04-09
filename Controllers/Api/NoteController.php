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
      $type = $this->getRequestBody('type');
      $id_user = $this->getRequestBody('id_user');
      $id_project = $this->getRequestBody('id_project');
      $is_public = $this->getRequestBody('is_public');
      $id_programming_language = $this->getRequestBody('id_programming_language');
      return $this->model->add(
        array(
          'title' => $title,
          'content' => $content,
          'type' => $type,
          'id_user' => $id_user,
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
      $id = $this->getUriSegments()[3];
      $title = $this->getRequestBody('title');
      $content = $this->getRequestBody('content');
      $is_public = $this->getRequestBody('is_public');
      return $this->model->modify(
        array(
          'id' => $id,
          'title' => $title,
          'content' => $content,
          'is_public' => $is_public
        )
      );
    });
  }
}