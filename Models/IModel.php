<?php
interface IModel {
  public function getAll(...$params);
  public function getOne($id);
  public function remove($id);
  public function add($paramsArray);
  public function modify($paramsArray);
}