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
}