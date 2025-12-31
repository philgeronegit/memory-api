<?php
class TechnicalSkillController extends BaseController
{
  public function __construct()
  {
    parent::__construct(new TechnicalSkillModel());
  }

  public function addAction(): void
  {
    $this->doAction($fn = function () {
      $name = $this->getRequestBody('name');
      return $this->model->add(array(
        'name' => $name
      ));
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

  public function addTechnicalSkillToUser(): void
  {
    $this->doAction($fn = function () {
      $user_id = $this->getUriSegments()[3];
      $id_technical_skill = $this->getUriSegments()[5];
      $year_experience = $this->getRequestBody('year_experience');

      return $this->sendOutput($this->model->addToTechnicalSkill(array(
        'user_id' => $user_id,
        'technical_skill_id' => $id_technical_skill,
        'year_experience' => $year_experience
      )));
    });
  }

  public function updateTechnicalSkillFromUser(): void
  {
    $this->doAction($fn = function () {
      $user_id = $this->getUriSegments()[3];
      $id_technical_skill = $this->getUriSegments()[5];
      $year_experience = $this->getRequestBody('year_experience');

      return $this->sendOutput($this->model->updateTechnicalSkill(array(
        'user_id' => $user_id,
        'technical_skill_id' => $id_technical_skill,
        'year_experience' => $year_experience
      )));
    });
  }

  public function removeTechnicalSkillFromUser(): void
  {
    $this->doAction($fn = function () {
      $user_id = $this->getUriSegments()[3];
      $id_technical_skill = $this->getUriSegments()[5];

      return $this->sendOutput($this->model->removeFromTechnicalSkill(array(
        'user_id' => $user_id,
        'technical_skill_id' => $id_technical_skill
      )));
    });
  }
}