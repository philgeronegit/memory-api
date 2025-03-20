<?php
class CommentController extends BaseController
{
  public function __construct()
  {
    parent::__construct(new CommentModel());
  }

  public function addAction(): void
  {
    $this->doAction($fn = function () {

      $content = $this->getRequestBody('content');
      $id_developer = $this->getRequestBody('id_developer');
      $id_item = $this->getRequestBody('id_item');
      return $this->model->add(array(
        'content' => $content,
        'id_developer' => $id_developer,
        'id_item' => $id_item
      ));
    });
  }

  public function updateAction(): void
  {
    $this->doAction($fn = function () {
      $id = $this->getQueryString('id');
      $content = $this->getRequestBody('content');
      return $this->model->modify(
        array(
          'id' => $id,
          'content' => $content
        )
      );
    });
  }
}