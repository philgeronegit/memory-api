<?php
require_once PROJECT_ROOT_PATH . "/Models/Database.php";
require_once PROJECT_ROOT_PATH . "/Models/IModel.php";

class TaskModel extends Database implements IModel
{
  public function __construct()
  {
    parent::__construct();

    $this->baseQuery = <<<SQL
      SELECT
          task.id_item,
          title,
          description,
          created_at,
          updated_at,
          archived_at,
          status.id_status,
          name AS status,
          due_at,
          done_at,
          priority,
          id_project,
          id_executive,
          id_developer,
          task.task_order
      FROM
          task
              JOIN
          item ON item.id_item = task.id_item
              JOIN
          status ON status.id_status = task.id_status

      SQL;
  }

  public function getAll($args)
  {
    $limit = $args['limit'];
    if (array_key_exists('id', $args)) {
      $id = $args['id'];
      $query = $this->baseQuery . <<<SQL
        WHERE id_developer = ?
        ORDER BY
          CASE
            WHEN updated_at IS NOT NULL THEN updated_at
            ELSE created_at
          END ASC
        LIMIT ?
      SQL;

      return $this->select($query, ["ii", $id, $limit]);
    }

    $query = $this->baseQuery . <<<SQL
    ORDER BY
      CASE
        WHEN updated_at IS NOT NULL THEN updated_at
        ELSE created_at
      END ASC
    LIMIT ?
    SQL;

    return $this->select($query, ["i", $limit]);
  }

  public function getOne($id, $args = null)
  {
    $query = $this->baseQuery . " WHERE task.id_item = ?";

    return $this->selectOne($query, ["i", $id]);
  }

  public function remove($id)
  {
    return $this->delete("DELETE FROM task WHERE task.id_item = ?", ["i", $id]);
  }

  public function add($paramsArray)
  {
    $title = $paramsArray['title'];
    $description = $paramsArray['description'];
    $id_status = $paramsArray['id_status'];
    $id_project = $paramsArray['id_project'];
    $id_executive = $paramsArray['id_executive'];
    $id_developer = $paramsArray['id_developer'];
    $priority = $paramsArray['priority'];
    $item_id = $this->insert(
      "INSERT INTO item (title, description) VALUES (?, ?)",
      ["ss", $title, $description]
    );

    $this->insert(
      "INSERT INTO task (id_item, id_status, id_project, id_executive, id_developer, priority) VALUES (?, ?, ?, ?, ?, ?)",
      ["iiiiis", $item_id, $id_status, $id_project, $id_executive, $id_developer, $priority]
    );

    $query = $this->baseQuery . <<<SQL
    WHERE item.id_item = ?
    SQL;
    return $this->selectOne($query, ["i", $item_id]);
  }

  public function modify($paramsArray)
  {
    $id = $paramsArray['id'];

    $query = $this->baseQuery . <<<SQL
    WHERE item.id_item = ?
    SQL;
    $task = $this->selectOne($query, ["i", $id]);

    $title = $paramsArray['title'] ?? $task->title;
    $description = $paramsArray['description'] ?? $task->description;
    $id_status = $paramsArray['id_status'] ?? $task->id_status;
    $priority = $paramsArray['priority'] ?? $task->priority;
    $updated_at = date('Y-m-d H:i:s');
    $due_at = $paramsArray['due_at'] ?? $task->due_at;
    $done_at = $paramsArray['done_at'] ?? $task->done_at;

    $this->update(
      "UPDATE item SET title = ?, description = ?, updated_at = ? WHERE item.id_item = ?",
      ["sssi", $title, $description, $updated_at, $id]
    );

    $this->update(
      "UPDATE task SET id_status = ?, priority = ?, due_at = ?, done_at = ? WHERE task.id_item = ?",
      ["isssi", $id_status, $priority, $due_at, $done_at, $id]
    );

    return $this->selectOne($query, ["i", $id]);
  }

  public function reorderTasks($paramsArray)
  {
    $tasks = $paramsArray['tasks'];

    if (empty($tasks)) {
      return false;
    }

    // Begin transaction to ensure data consistency
    $this->connection->begin_transaction();

    try {
      foreach ($tasks as $task) {
        $id = $task['id'];
        $order = $task['order'];

        // Update task order
        $updateQuery = "UPDATE task SET task_order = ?";
        $params = ["i", $order];

        // If status is provided, update it as well
        if (isset($task['status'])) {
          // First, get the status ID from status name
          $statusQuery = "SELECT id_status FROM status WHERE name = ?";
          $statusResult = $this->selectOne($statusQuery, ["s", $task['status']]);

          if ($statusResult) {
            $updateQuery .= ", id_status = ?";
            $params[0] .= "i";
            $params[] = $statusResult->id_status;
          }
        }

        $updateQuery .= " WHERE id_item = ?";
        $params[0] .= "i";
        $params[] = $id;

        $this->update($updateQuery, $params);
      }

      // Commit the transaction
      $this->connection->commit();
      return true;

    } catch (Exception $e) {
      // Rollback the transaction on error
      $this->connection->rollback();
      throw $e;
    }
  }
}