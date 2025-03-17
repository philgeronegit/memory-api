<?php
class ProgrammingLanguageController extends BaseController
{
  public function __construct()
  {
    parent::__construct(new ProgrammingLanguageModel());
  }
}