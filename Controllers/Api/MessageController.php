<?php
class MessageController extends BaseController
{
  public function __construct()
  {
    parent::__construct(new MessageModel());
  }

  public function addAction(): void
  {
    $this->doAction($fn = function () {
      $text = $this->getRequestBody('text');
      $id_user = $this->getRequestBody('id_user');
      return $this->model->add(array('text' => $text, 'id_user' => $id_user));
    });
  }

  public function addMessageToUser(): void
  {
    $this->doAction($fn = function () {
      $user_id = $this->getUriSegments()[3];
      $text = $this->getRequestBody('text');
      return $this->model->addToUser(array('text' => $text, 'user_id' => $user_id));
    });
  }

  public function modifyMessageForUser(): void
  {
    $this->doAction($fn = function () {
      $user_id = $this->getUriSegments()[3];
      $message_id = $this->getUriSegments()[5];
      $read_at = $this->getRequestBody('read_at');
      return $this->model->modifyForUser(
        array(
          'message_id' => $message_id,
          'user_id' => $user_id,
          'read_at' => $read_at)
        );
    });
  }

  public function updateAction(): void
  {
    $this->doAction($fn = function () {
      $id = $this->getUriSegments()[3];
      $text = $this->getRequestBody('text');
      return $this->model->modify(
        array(
          'id' => $id,
          'text' => $text
        )
      );
    });
  }
}