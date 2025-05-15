<?php
interface IModel {
  public function getAll($args);
  public function getOne($id, $args = null);
  public function remove($id);
  public function add($paramsArray);
  public function modify($paramsArray);
}