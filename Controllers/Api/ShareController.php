<?php
class ShareController extends BaseController
{
  public function __construct()
  {
    parent::__construct(new ShareModel());
  }
}