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

      // Validate required fields to prevent incomplete note creation
      if (empty($title) || empty($content) || empty($type) || empty($id_user)) {
          throw new Exception("Missing required fields for note creation.");
      }

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

  protected function hasUpdateOrRemovePermissions($noteId): bool {
    $currentUserId = $this->getCurrentUserId();
    $note = $this->model->getOne($noteId);

    $isCurrentUser = $note->id_user === $currentUserId;
    $hasPermission = $this->hasPermission('admin');

    return $isCurrentUser || $hasPermission;
  }

  public function updateAction(): void
  {
    $this->doAction($fn = function () {
      $id = $this->getUriSegments()[3];

      if (!$this->hasUpdateOrRemovePermissions($id)) {
        throw new Exception("Unauthorized: You do not have permission to update this note.");
      }

      $user_id = $this->getRequestBody('user_id');
      $title = $this->getRequestBody('title');
      $content = $this->getRequestBody('content');
      $is_public = $this->getRequestBody('is_public');
      $id_project = $this->getRequestBody('id_project');
      $id_programming_language = $this->getRequestBody('id_programming_language');

      // Validate required fields to prevent incomplete note updates
      if (empty($id) || (empty($title) && empty($content))) {
          throw new Exception("Missing required fields for note update.");
      }

      return $this->model->modify(
        array(
          'id' => $id,
          'user_id' => $user_id,
          'title' => $title,
          'content' => $content,
          'is_public' => $is_public,
          'id_programming_language' => $id_programming_language,
          'id_project' => $id_project
        )
      );
    });
  }

  public function shareNoteWithUser(): void
  {
    $this->doAction($fn = function () {
      $id_item = $this->getRequestBody('id_item');
      $id_user = $this->getRequestBody('id_user');
      return $this->model->shareNoteWithUser(
        array(
          'id_item' => $id_item,
          'id_user' => $id_user
        )
      );
    });
  }

  public function removeAction(): void
  {
    $this->doAction($fn = function () {
      $id = $this->getUriSegments()[3];

      if (!$this->hasUpdateOrRemovePermissions($id)) {
        throw new Exception("Unauthorized: You do not have permission to delete this note.");
      }

      return $this->model->remove($id);
    });
  }
}