<?php
class ProjectController extends BaseController
{
  public function __construct()
  {
    parent::__construct(new ProjectModel());
  }
}