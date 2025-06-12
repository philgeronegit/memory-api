<?php
class MessageController extends BaseController
{
  public function __construct()
  {
    parent::__construct(new MessageModel());
  }

  public function addMessageToUser(): void
  {
    $this->doAction($fn = function () {
      $message_id = $this->getUriSegments()[3];
      $user_id = $this->getUriSegments()[5];
      return $this->model->addToUser(array('message_id' => $message_id, 'user_id' => $user_id));
    });
  }

  public function addAction(): void
  {
    $this->doAction($fn = function () {
      $text = $this->getRequestBody('text');
      return $this->model->add(array('text' => $text));
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