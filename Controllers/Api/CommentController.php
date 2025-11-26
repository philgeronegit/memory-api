<?php
class CommentController extends BaseController
{
  protected $noteModel;

  public function __construct()
  {
    parent::__construct(new CommentModel());
    $this->noteModel = new NoteModel();
  }

  public function addAction(): void
  {
    $this->doAction($fn = function () {
      $content = $this->getRequestBody('content');
      $id_user = $this->getRequestBody('id_user');
      $id_item = $this->getRequestBody('id_item');

      // check if required fields are present
      // return error whith http 400
      if (empty($content) || empty($id_user) || empty($id_item)) {
        $this->sendOutput('Missing required fields: content, id_user, or id_item.', array('HTTP/1.1 400 Bad Request'));
        return;
      }

      // Verify user has access to the note before allowing comment
      if (!$this->noteModel->userHasNoteAccess($id_item, $id_user)) {
        $this->sendOutput('Unauthorized to update this user', array('HTTP/1.1 403 Forbidden'));
        return;
      }

      return $this->model->add(array(
        'content' => $content,
        'id_user' => $id_user,
        'id_item' => $id_item
      ));
    });
  }

  public function updateAction(): void
  {
    $this->doAction($fn = function () {
      $id = $this->getUriSegments()[3];
      $user_id = $this->getRequestBody('user_id');
      $content = $this->getRequestBody('content');
      $score = $this->getRequestBody('score');
      return $this->model->modify(
        array(
          'id' => $id,
          'user_id' => $user_id,
          'content' => $content,
          'score' => $score
        )
      );
    });
  }
}
