<?php
interface IValidator {
  public function validate($paramsArray): array;
}