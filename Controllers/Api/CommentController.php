<?php
class CommentController extends BaseController
{
  public function __construct()
  {
    parent::__construct(new CommentModel());
  }
}