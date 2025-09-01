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
          id_developer
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
    $id = $this->insert(
      "INSERT INTO item (title, description) VALUES (?, ?)",
      ["ss", $title, $description]
    );

    $this->insert(
      "INSERT INTO task (id_item, id_status) VALUES (?, ?)",
      ["ii", $id, $id_status]
    );
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

    $this->update(
      "UPDATE item SET title = ?, description = ? WHERE item.id_item = ?",
      ["ssi", $title, $description, $id]
    );

    $this->update(
      "UPDATE task SET id_status = ? WHERE task.id_item = ?",
      ["ii", $id_status, $id]
    );

    return $this->selectOne($query, ["i", $id]);
  }
}