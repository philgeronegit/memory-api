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
   * @param string $httpHeader
   */
  protected function sendOutput($data, $httpHeaders = array())
  {
    if (is_array($httpHeaders) && count($httpHeaders)) {
      foreach ($httpHeaders as $httpHeader) {
        header($httpHeader);
      }
    }
    echo $data;
    exit;
  }

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
    } catch (Error $e) {
      $strErrorDesc = $e->getMessage() . 'Something went wrong! Please contact support.';
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

      return $this->model->getAll($args);
    }, $args);
  }

  public function removeAction(): void
  {
    $this->doAction($fn = function () {
      $id = $this->getUriSegments()[3];
      return $this->model->remove($id);
    });
  }

  public function getAction(): void
  {
    $this->doAction($fn = function () {
      $id = $this->getUriSegments()[3];
      $args = $this->getQueryStringParams();
      return $this->model->getOne($id, $args);
    });
  }
}