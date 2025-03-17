<?php
class RoleController extends BaseController
{
  public function __construct()
  {
    parent::__construct(new RoleModel());
  }
}