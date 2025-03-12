<?php
class TagController extends BaseController
{
  public function __construct()
  {
    parent::__construct(new TagModel());
  }
}