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
      return $this->model->add(array('title' => $title, 'content' => '', 'type' => 'text'));
    });
  }
}