<?php
class CommentsController extends BaseController
{
  public function __construct()
  {
    parent::__construct(new CommentsModel());
  }
}