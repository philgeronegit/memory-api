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