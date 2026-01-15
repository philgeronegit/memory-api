<?php
class BaseController
{
  protected $model;

  public function __construct($model)
  {
    $this->model = $model;
  }

  /**
   * __call magic method.
   * Called when a method doesn't exist.
   */
  public function __call($name, $arguments)
  {
    $this->sendOutput('', array('HTTP/1.1 404 Not Found'));
  }

  /**
   * Get authenticated user data from JWT token
   *
   * @return object|null
   */
  protected function getAuthenticatedUser()
  {
    return $GLOBALS['jwt_user_data'] ?? null;
  }

  /**
   * Check if the authenticated user has permission to perform the action.
   * Only users with roles 'projectManager' or 'admin' are allowed.
   *
   * @return bool
   */
  protected function hasUserModificationPermission(): bool {
    $currentUser = $this->getAuthenticatedUser();

    if (!in_array($currentUser->role, ['projectManager', 'admin'])) {
        $logger = SecurityLogger::getInstance();
        $logger->logAuthorizationFailure(
            'user_modification',
            'modify',
            $currentUser->id_user ?? null,
            $currentUser->role ?? 'unknown'
        );
        return false;
    }
    return true;
  }

  /**
   * Check if a user has a specific role/permission.
   *
   * @param string $requiredRole
   * @return bool
   */
  protected function hasPermission($requiredRole): bool {
    $user = $this->getAuthenticatedUser();

    // Check if the user's role matches the required role
    $hasAccess = $user->role === $requiredRole;

    if (!$hasAccess) {
        $logger = SecurityLogger::getInstance();
        $logger->logAuthorizationFailure(
            'role_check',
            $requiredRole,
            $user->id_user ?? null,
            $user->role ?? 'unknown'
        );
    }

    return $hasAccess;
  }

  /**
   * Get the ID of the currently authenticated user.
   *
   * @return int|null
   */
  protected function getCurrentUserId()
  {
    $currentUser = $this->getAuthenticatedUser();
    return $currentUser ? $currentUser->id_user : null;
  }

  /**
   * Map controller class name to resource name
   *
   * @param string $controllerClass
   * @return string|null
   */
  protected function getResourceNameFromController($controllerClass)
  {
    $mapping = [
      'NoteController' => 'notes',
      'UserController' => 'users',
      'ProjectController' => 'projects',
      'TaskController' => 'tasks',
      'CommentController' => 'comments',
      'MessageController' => 'messages',
      'TagController' => 'tags',
      'RoleController' => 'roles',
      'DeveloperController' => 'developers',
      'ProgrammingLanguageController' => 'programmingLanguages',
      'StatusController' => 'statuses',
      'ScoreController' => 'scores',
      'ShareController' => 'shares',
      'TechnicalSkillController' => 'technicalSkills',
    ];

    return $mapping[$controllerClass] ?? null;
  }

  /**
   * Check if a resource requires read permission
   *
   * @param string $resourceName
   * @return bool
   */
  protected function requiresReadPermission($resourceName)
  {
    $protectedResources = ['notes', 'users', 'projects', 'tasks', 'messages'];

    return in_array($resourceName, $protectedResources);
  }

  /**
   * Get URI elements.
   * Returns an array of URI elements.
   *
   * @return array
   */
  protected function getUriSegments()
  {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = explode('/', $uri);
    return $uri;
  }

  /**
   * Get querystring params.
   * Returns an array of query string parameters.
   *
   * @return array
   */
  protected function getQueryStringParams()
  {
    parse_str($_SERVER['QUERY_STRING'], $query);

    return $query;
  }

  protected function getRequestBody($name)
  {
    $data = json_decode(file_get_contents('php://input'), true);
    $value = isset($data[$name]) ? $data[$name] : null;

    return $this->sanitizeInput($value);
  }

  /**
   * Sanitize input data.
   * Removes HTML tags and trims whitespace.
   * @param mixed $value
   * @return mixed
   */
  protected function sanitizeInput($value)
  {
    if (is_array($value)) {
      return array_map([$this, 'sanitizeInput'], $value);
    } elseif (is_string($value)) {
      // Remove HTML tags and trim whitespace
      return trim(strip_tags($value));
    }
    return $value; // Return as is for non-string and non-array types
  }

  /**
   * Send API output.
   *
    * @param mixed $data
    * @param array $httpHeaders
   */
  protected function sendOutput($data, $httpHeaders = array())
  {
    if (is_array($httpHeaders) && count($httpHeaders)) {
      foreach ($httpHeaders as $httpHeader) {
        header($httpHeader);
      }
    }
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
  }

  /**
   * Get a query string parameter value.
   * @param string $name
   * @param mixed $default
   * @return mixed
   */
  protected function getQueryString($name, $default = null)
  {
    $arrQueryStringParams = $this->getQueryStringParams();
    $query_string = $default;
    if (isset($arrQueryStringParams[$name]) && $arrQueryStringParams[$name]) {
      $query_string = $arrQueryStringParams[$name];
    }

    return $query_string;
  }

  protected function doAction($fn, $args = [])
  {
    $strErrorDesc = '';
    $strErrorHeader = 'HTTP/1.1 500 Internal Server Error';
    $responseData = '';
    $requestMethod = $_SERVER["REQUEST_METHOD"];
    $arrQueryStringParams = $this->getQueryStringParams();

    try {
      $res = $fn($args);
      // check if res is of type array

      if (is_array($res) and array_key_exists("error", $res)) {
        $strErrorDesc = $res['error'];
        $strErrorHeader = 'HTTP/1.1 401 Unauthorized';
      } else {
        $responseData = json_encode($res);
      }
    } catch (Throwable $e) {
      $strErrorDesc = $e->getMessage();
      $strErrorHeader = 'HTTP/1.1 500 Internal Server Error';
    }
    // send output
    if (!$strErrorDesc) {
      $this->sendOutput(
        $responseData,
        array('Content-Type: application/json', 'HTTP/1.1 200 OK')
      );
    } else {
      $this->sendOutput(
        json_encode(array('error' => $strErrorDesc)),
        array('Content-Type: application/json', $strErrorHeader)
      );
    }
  }

  public function listAction($args = [])
  {
    $this->doAction($fn = function ($args) {
      $controllerClass = get_class($this);
      $resourceName = $this->getResourceNameFromController($controllerClass);

      if ($resourceName && $this->requiresReadPermission($resourceName)) {
        $user = $this->getAuthenticatedUser();
        $authService = AuthorizationService::getInstance();

        if (!$authService->can($user, $resourceName, 'view')) {
          $authService->logAuthorizationFailure(
            $resourceName,
            'view',
            $user->id_user ?? null,
            $user->role ?? 'unknown'
          );
          throw new Exception("You do not have permission to view $resourceName");
        }
      }

      $intLimit = $this->getQueryString('limit', 50);
      $args['limit'] = $intLimit;

      $count = $this->getQueryString('count');
      if ($count) {
        $args['count'] = $count;
      }

      $search = $this->getQueryString('search');
      if ($search) {
        $args['search'] = $search;
      }
      $searchType = $this->getQueryString('search_type', 'all');
      if ($searchType) {
        $args['search_type'] = $searchType;
      }

      return $this->sendOutput($this->model->getAll($args));
    }, $args);
  }

  public function removeAction(): void
  {
    $this->doAction($fn = function () {
      $id = $this->getUriSegments()[3];
      try {
        $this->sendOutput($this->model->remove($id));
      } catch (Exception $e) {
        $errorMessage = $e->getMessage();
        $this->sendOutput(['error' => $errorMessage], array('HTTP/1.1 400 Bad Request'));
        return;
      }
    });
  }

  public function getAction(): void
  {
    $this->doAction($fn = function () {
      $controllerClass = get_class($this);
      $resourceName = $this->getResourceNameFromController($controllerClass);

      if ($resourceName && $this->requiresReadPermission($resourceName)) {
        $user = $this->getAuthenticatedUser();
        $authService = AuthorizationService::getInstance();

        if (!$authService->can($user, $resourceName, 'view')) {
          $authService->logAuthorizationFailure(
            $resourceName,
            'view',
            $user->id_user ?? null,
            $user->role ?? 'unknown'
          );
          throw new Exception("You do not have permission to view this $resourceName");
        }
      }

      $id = $this->getUriSegments()[3];
      $args = $this->getQueryStringParams();
      return $this->sendOutput($this->model->getOne($id, $args));
    });
  }
}