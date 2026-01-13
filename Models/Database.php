<?php
class Database
{
  protected $connection = null;
  protected $baseQuery = null;
  protected $baseQuerySelect = null;
  protected $baseQueryGroupBy = null;

  public function __construct()
  {
    try {
      $this->connection = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DATABASE_NAME);

      if (mysqli_connect_errno()) {
        throw new Exception("Could not connect to database.");
      }
    } catch (Exception $e) {
      throw new Exception($e->getMessage());
    }
  }

  /**
   * Execute a SELECT query and return the results as an associative array.
   * @param string $query The SQL query to execute.
   * @param array $params The parameters to bind to the query.
   * @return array The result set as an associative array.
   */
  public function select($query = "", $params = [])
  {
    try {
      $statement = $this->executeStatement($query, $params);
      $result = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
      $statement->close();
      return $result;
    } catch (Exception $e) {
      throw new Exception($e->getMessage());
    }
  }

  /**
   * Execute a SELECT query and return a single result as an object.
   * @param string $query The SQL query to execute.
   * @param array $params The parameters to bind to the query.
   * @return object The result as an object.
   */
  public function selectOne($query = "", $params = []): object
  {
    try {
      $statement = $this->executeStatement($query, $params);
      $result = $statement->get_result()->fetch_object();
      $statement->close();
      if (!$result) {
        return (object)[];
      }
      return $result;
    } catch (Exception $e) {
      throw new Exception($e->getMessage());
    }
  }

  /**
   * Execute an INSERT query and return the inserted ID.
   * @param string $query The SQL query to execute.
   * @param array $params The parameters to bind to the query.
   * @return int The ID of the inserted row.
   */
  public function insert($query = "", $params = [])
  {
    try {
      $statement = $this->executeStatement($query, $params);
      $statement->close();
      return $this->connection->insert_id;
    } catch (Exception $e) {
      throw new Exception($e->getMessage());
    }
  }

  /**
   * Execute an UPDATE query.
   * @param string $query The SQL query to execute.
   * @param array $params The parameters to bind to the query.
   * @return bool True on success.
   */
  public function update($query = "", $params = [])
  {
    try {
      $statement = $this->executeStatement($query, $params);
      $statement->close();
      return true;
    } catch (Exception $e) {
      throw new Exception($e->getMessage());
    }
  }

  /**
   * Execute a DELETE query.
   * @param string $query The SQL query to execute.
   * @param array $params The parameters to bind to the query.
   * @return bool True on success.
   */
  public function delete($query = "", $params = [])
  {
    try {
      $statement = $this->executeStatement($query, $params);
      $statement->close();
      return true;
    } catch (Exception $e) {
      throw new Exception($e->getMessage());
    }
  }

  /**
   * Execute a prepared statement with the given query and parameters.
   * @param string $query The SQL query to execute.
   * @param array $params The parameters to bind to the query.
   * @return mysqli_stmt The executed statement.
   */
  private function executeStatement($query = "", $params = [])
  {
    try {
      $statement = $this->connection->prepare($query);
      if ($statement === false) {
        throw new Exception("Unable to do prepared statement: " . $query);
      }
      if ($params) {
        $statement->bind_param($params[0], ...array_slice($params, 1));
      }
      $statement->execute();
      return $statement;
    } catch (Exception $e) {
      throw new Exception($e->getMessage());
    }
  }
}