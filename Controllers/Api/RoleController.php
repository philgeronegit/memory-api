<?php
class RoleController extends BaseController
{
  public function __construct()
  {
    parent::__construct(new RoleModel());
  }

  public function getPermissionsByRole($id_role)
  {
    return $this->sendOutput($this->model->getPermissionsByRole($id_role));
  }
}