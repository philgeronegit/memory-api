<?php
class TaskController extends BaseController
{
  public function __construct()
  {
    parent::__construct(new TaskModel());
  }
}