<?php
define("PROJECT_ROOT_PATH", __DIR__ . "/../");

// include main configuration file
require_once PROJECT_ROOT_PATH . "/inc/config.php";
// include the base controller file
require_once PROJECT_ROOT_PATH . "/Controllers/Api/BaseController.php";
// Require all files in a loop from /Models except Database.php
for ($i = 0; $i < count(glob(PROJECT_ROOT_PATH . "/Models/*.php")); $i++) {
  if (glob(PROJECT_ROOT_PATH . "/Models/*.php")[$i] !== PROJECT_ROOT_PATH . "/Models/Database.php") {
    require_once glob(PROJECT_ROOT_PATH . "/Models/*.php")[$i];
  }
}

// Require all files in a loop from /Controllers/Api except BaseController.php
for ($i = 0; $i < count(glob(PROJECT_ROOT_PATH . "/Controllers/Api/*.php")); $i++) {
  if (glob(PROJECT_ROOT_PATH . "/Controllers/Api/*.php")[$i] !== PROJECT_ROOT_PATH . "/Controllers/Api/BaseController.php") {
    require_once glob(PROJECT_ROOT_PATH . "/Controllers/Api/*.php")[$i];
  }
}