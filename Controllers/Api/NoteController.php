<?php
class NoteController extends BaseController
{
  private $noteService;

  public function __construct()
  {
    parent::__construct(new NoteModel());
    $noteService = new NoteService($this->model);
    $this->noteService = $noteService;
  }

  public function addAction(): void
  {
    $this->doAction($fn = function () {
      $data = [
        'title' => $this->getRequestBody('title'),
        'content' => $this->getRequestBody('content'),
        'type' => $this->getRequestBody('type'),
        'id_user' => $this->getRequestBody('id_user'),
        'id_project' => $this->getRequestBody('id_project'),
        'is_public' => $this->getRequestBody('is_public'),
        'id_programming_language' => $this->getRequestBody('id_programming_language'),
      ];

      return $this->sendOutput($this->noteService->createNote($data));
    });
  }

  public function updateAction(): void
  {
    $this->doAction($fn = function () {
      $id = $this->getUriSegments()[3];

      $data = [
        'user_id' => $this->getRequestBody('user_id'),
        'title' => $this->getRequestBody('title'),
        'content' => $this->getRequestBody('content'),
        'is_public' => $this->getRequestBody('is_public'),
        'id_project' => $this->getRequestBody('id_project'),
        'id_programming_language' => $this->getRequestBody('id_programming_language'),
      ];

      return $this->noteService->updateNote($id, $data);
    });
  }

  public function shareNoteWithUser(): void
  {
    $this->doAction($fn = function () {
      $data = [
        'id_item' => $this->getRequestBody('id_item'),
        'id_user' => $this->getRequestBody('id_user'),
      ];

      return $this->sendOutput($this->noteService->shareNote($data));
    });
  }

  public function removeAction(): void
  {
    $this->doAction($fn = function () {
      $id = $this->getUriSegments()[3];
      return $this->noteService->deleteNote($id);
    });
  }
}