<?php
class TagController extends BaseController
{
  public function __construct()
  {
    parent::__construct(new TagModel());
  }

  public function addTagToNote(): void
  {
    $this->doAction($fn = function () {
      $note_id = $this->getUriSegments()[3];
      $tag_id = $this->getUriSegments()[5];
      return $this->model->addToNote(array('note_id' => $note_id, 'tag_id' => $tag_id));
    });
  }

  public function removeTagToNote(): void
  {
    $this->doAction($fn = function () {
      $note_id = $this->getUriSegments()[3];
      $tag_id = $this->getUriSegments()[5];
      return $this->model->removeToNote(array('note_id' => $note_id, 'tag_id' => $tag_id));
    });
  }

  public function addAction(): void
  {
    $this->doAction($fn = function () {

      $name = $this->getRequestBody('name');
      return $this->model->add(array('name' => $name));
    });
  }

  public function updateAction(): void
  {
    $this->doAction($fn = function () {
      $id = $this->getUriSegments()[3];
      $name = $this->getRequestBody('name');
      return $this->model->modify(
        array(
          'id' => $id,
          'name' => $name
        )
      );
    });
  }
}