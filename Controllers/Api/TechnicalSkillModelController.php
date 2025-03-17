<?php
class TechnicalSkillController extends BaseController
{
  public function __construct()
  {
    parent::__construct(new TechnicalSkillModel());
  }
}