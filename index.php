<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    exit(0);
}

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

require_once __DIR__ . "/inc/bootstrap.php";

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

$hasRoute = isset($uri[2]);
if (!$hasRoute) {
  header("HTTP/1.1 404 Not Found");
  exit();
}

$requestMethod = $_SERVER["REQUEST_METHOD"];

$hasAdditionalSegment = isset($uri[4]);
if ($hasAdditionalSegment) {
  if ($uri[2] === 'user' and $uri[4] === 'upload' and $requestMethod === 'GET') {
    $objController = new UploadController();
    $args = array(
      'id' => $uri[3]
    );
    $objController->listAction($args);
    exit();
  }
  if ($uri[2] === 'user' and $uri[4] === 'message' and $requestMethod === 'GET') {
    $objController = new MessageController();
    $args = array(
      'id' => $uri[3]
    );
    $objController->listAction($args);
    exit();
  }
  if ($uri[2] === 'user' and $uri[4] === 'technical-skill' and $requestMethod === 'GET') {
    $objController = new TechnicalSkillController();
    $args = array(
      'id' => $uri[3]
    );
    $objController->listAction($args);
    exit();
  }
  if ($uri[2] === 'note' and $uri[4] === 'comment' and $requestMethod === 'GET') {
    $objController = new CommentController();
    $args = array(
      'id' => $uri[3]
    );
    $objController->listAction($args);
    exit();
  }
  if ($uri[2] === 'note' and $uri[4] === 'tag' and $requestMethod === 'GET') {
    $objController = new TagController();
    $args = array(
      'id' => $uri[3]
    );
    $objController->listAction($args);
    exit();
  }
  if ($uri[2] === 'note' and $uri[4] === 'score' and $requestMethod === 'POST') {
    $objController = new ScoreController();
    $objController->addAction();
    exit();
  }
  if ($uri[2] === 'note' and $uri[4] === 'score' and $requestMethod === 'PUT') {
    $objController = new ScoreController();
    $objController->updateScoreToNote();
    exit();
  }
  if ($uri[2] === 'note' and $uri[4] === 'tag' and $requestMethod === 'PUT') {
    $objController = new TagController();
    $objController->updateTagToNote();
    exit();
  }
  if ($uri[2] === 'note' and $uri[4] === 'tag' and $requestMethod === 'POST') {
    $objController = new TagController();
    $objController->addTagToNote();
    exit();
  }
  if ($uri[2] === 'note' and $uri[4] === 'tag' and $requestMethod === 'DELETE') {
    $objController = new TagController();
    $objController->removeTagToNote();
    exit();
  }
}

$objController = null;
switch ($uri[2]) {
  case 'login':
    $objController = new LoginController();
    break;
  case 'comment':
    $objController = new CommentController();
    break;
  case 'developer':
    $objController = new DeveloperController();
    break;
  case 'note':
    $objController = new NoteController();
    break;
  case 'programming-language':
    $objController = new ProgrammingLanguageController();
    break;
  case 'project':
    $objController = new ProjectController();
    break;
  case 'role':
    $objController = new RoleController();
    break;
  case 'tag':
    $objController = new TagController();
    break;
  case 'task':
    $objController = new TaskController();
    break;
  case 'user':
    $objController = new UserController();
    break;
  case 'technical-skill':
    $objController = new TechnicalSkillController();
    break;
  case 'upload':
    $objController = new UploadController();
    break;
  default:
    header("HTTP/1.1 404 Not Found");
    exit();
}

if ($requestMethod === 'POST') {
  $prefix = 'add';
} elseif ($requestMethod === 'PUT') {
  $prefix = 'update';
} elseif ($requestMethod === 'DELETE') {
  $prefix = 'remove';
} elseif ($requestMethod === 'GET') {
  $prefix = (isset($uri[3]) ? 'get' : 'list');
} else {
  header('Content-Type: application/json');
  header("HTTP/1.1 422 Unprocessable Entity");
  echo json_encode(array('error' => 'Method not supported'));
  exit();
}
$strMethodName = $prefix . 'Action';
$objController->{$strMethodName}();