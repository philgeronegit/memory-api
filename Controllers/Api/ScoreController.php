<?php
class ScoreController extends BaseController
{
  public function __construct()
  {
    parent::__construct(new ScoreModel());
  }

  public function updateAction(): void
  {
    $this->doAction($fn = function () {
      $id = $this->getUriSegments()[3];
      $user_id = $this->getUriSegments()[5];
      $score = $this->getRequestBody('score');
      return $this->model->modify(
        array(
          'id' => $id,
          'user_id' => $user_id,
          'score' => $score
        )
      );
    });
  }

  public function updateScoreToNote(): void
  {
    $this->doAction($fn = function () {
      $note_id = $this->getUriSegments()[3];
      $user_id = $this->getRequestBody('user_id');
      $score = $this->getRequestBody('score');
      return $this->model->modify(array('id' => $note_id, 'user_id' => $user_id, 'score' => $score));
    });
  }

  public function addAction(): void
  {
    $this->doAction($fn = function () {
      $note_id = $this->getUriSegments()[3];
      $user_id = $this->getUriSegments()[5];
      $score = $this->getRequestBody('score');
      return $this->model->add(array('note_id' => $note_id, 'user_id' => $user_id, 'score' => $score));
    });
  }
}