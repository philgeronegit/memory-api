<?php
class StatusController extends BaseController
{
  public function __construct()
  {
    parent::__construct(new StatusModel());
  }
}