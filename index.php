<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle CORS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: http://localhost:3000");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
    exit(0);
}

// Set CORS headers for all other requests
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

require_once __DIR__ . "/inc/bootstrap.php";

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function verifyJwtToken() {
    $logger = SecurityLogger::getInstance();
    $headers = getallheaders();
    $headers = array_change_key_case($headers, CASE_LOWER);
    $authHeader = $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? null;

    if (!$authHeader && isset($_COOKIE['auth-token'])) {
        $authHeader = 'Bearer ' . $_COOKIE['auth-token'];
    }

    if (!$authHeader) {
        $logger->logTokenValidationFailure('Authorization header missing');
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Authorization header missing']);
        exit();
    }

    if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $logger->logTokenValidationFailure('Invalid authorization header format');
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid authorization header format']);
        exit();
    }

    $token = $matches[1];

    try {
        $secretKey = JWT_SECRET_KEY;
        $algorithm = JWT_ALGORITHM;
        $decoded = JWT::decode($token, new Key($secretKey, $algorithm));

        // Token is valid, return decoded payload
        return $decoded;
    } catch (Exception $e) {
        $logger->logTokenValidationFailure($e->getMessage(), $token);
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid or expired token: ' . $e->getMessage()]);
        exit();
    }
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

$hasRoute = isset($uri[2]);
if (!$hasRoute) {
  header("HTTP/1.1 404 Not Found");
  exit();
}

$requestMethod = $_SERVER["REQUEST_METHOD"];

// JWT verification for all routes except login
if ($uri[2] !== 'login' && $uri[2] !== 'view') {
    $decodedToken = verifyJwtToken();
    // Store decoded token data in a global variable for controllers to access if needed
    $GLOBALS['jwt_user_data'] = $decodedToken->data ?? null;
}

$hasAdditionalSegment = isset($uri[4]);
if ($hasAdditionalSegment) {
  if ($uri[2] === 'project' and $uri[4] === 'user' and $requestMethod === 'GET') {
    $objController = new UserController();
    $args = array(
      'id' => $uri[3]
    );
    $objController->listAction($args);
    exit();
  }
  if ($uri[2] === 'user' and $uri[4] === 'project' and $requestMethod === 'GET') {
    $objController = new ProjectController();
    $args = array(
      'id' => $uri[3]
    );
    $objController->listAction($args);
    exit();
  }
  if ($uri[2] === 'user' and $uri[4] === 'upload' and $requestMethod === 'GET') {
    $objController = new UploadController();
    $args = array(
      'id' => $uri[3]
    );
    $objController->listAction($args);
    exit();
  }
  if ($uri[2] === 'upload' and isset($uri[3]) and isset($uri[4]) and $requestMethod === 'GET') {
    $objController = new UploadController();
    $args = array(
      'id' => $uri[3],
      'filename' => $uri[4]
    );
    $objController->downloadAction($args);
    exit();
  }
  if ($uri[2] === 'view' and isset($uri[3]) and isset($uri[4]) and $requestMethod === 'GET') {
    $objController = new UploadController();
    $args = array(
      'id' => $uri[3],
      'filename' => $uri[4]
    );
    $objController->viewAction($args);
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
  if ($uri[2] === 'user' and $uri[4] === 'message' and $requestMethod === 'POST') {
    $objController = new MessageController();
    $objController->addMessageToUser();
    exit();
  }
  if ($uri[2] === 'user' and $uri[4] === 'message' and $requestMethod === 'PUT') {
    $objController = new MessageController();
    $objController->modifyMessageForUser();
    exit();
  }
  if ($uri[2] === 'user' and $uri[4] === 'note' and $requestMethod === 'GET') {
    $objController = new NoteController();
    $args = array(
      'id' => $uri[3]
    );
    $objController->listAction($args);
    exit();
  }
  if ($uri[2] === 'user' and $uri[4] === 'task' and $requestMethod === 'GET') {
    $objController = new TaskController();
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
  if ($uri[2] === 'user' and $uri[4] === 'project' and $requestMethod === 'POST') {
    $objController = new ProjectController();
    $objController->addProjectToUser();
    exit();
  }
  if ($uri[2] === 'user' and $uri[4] === 'project' and $requestMethod === 'DELETE') {
    $objController = new ProjectController();
    $objController->removeProjectFromUser();
    exit();
  }
  if ($uri[2] === 'user' and $uri[4] === 'technical-skill' and $requestMethod === 'POST') {
    $objController = new TechnicalSkillController();
    $objController->addTechnicalSkillToUser();
    exit();
  }
  if ($uri[2] === 'user' and $uri[4] === 'technical-skill' and $requestMethod === 'PUT') {
    $objController = new TechnicalSkillController();
    $objController->updateTechnicalSkillFromUser();
    exit();
  }
  if ($uri[2] === 'user' and $uri[4] === 'technical-skill' and $requestMethod === 'DELETE') {
    $objController = new TechnicalSkillController();
    $objController->removeTechnicalSkillFromUser();
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
  if ($uri[2] === 'note' and $uri[4] === 'share' and $requestMethod === 'GET') {
    $objController = new ShareController();
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
  if ($uri[2] === 'note' and $uri[4] === 'share' and $requestMethod === 'POST') {
    $objController = new NoteController();
    $objController->shareNoteWithUser();
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
  case 'message':
    $objController = new MessageController();
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
  case 'status':
    $objController = new StatusController();
    break;
  case 'tag':
    $objController = new TagController();
    break;
  case 'task':
    if (isset($uri[3]) and $uri[3] === 'order' and $requestMethod === 'PUT') {
      $objController = new TaskController();
      $objController->reorderTasks();
      exit();
    }
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