<?php
require_once PROJECT_ROOT_PATH . "/Models/Database.php";
require_once PROJECT_ROOT_PATH . "/Models/IModel.php";

class NoteModel extends Database implements IModel
{
  private $baseQuery;

  public function __construct()
  {
    parent::__construct();

    $this->baseQuery = <<<SQL
      SELECT
        item.id_item AS id_note,
        item.title,
        item.description AS content,
        note.type,
        note.is_public,
        item.created_at,
        item.updated_at,
        item.archived_at,
        note.id_programming_language,
        note.id_project,
        note.id_developer
      FROM
        item
      INNER JOIN note ON item.id_item = note.id_item

      SQL;
  }

  public function getAll(...$params)
  {
    $limit = $params[0];
    $query = $this->baseQuery . <<<SQL
    ORDER BY id_note ASC
    LIMIT ?
    SQL;

    return $this->select($query, ["i", $limit]);
  }

  public function getOne($id)
  {
    $query = $this->baseQuery . " WHERE item.id_item = ?";

    return $this->selectOne($query, ["i", $id]);
  }

  public function remove($id)
  {
    return $this->delete("DELETE FROM note WHERE id_item = ?", ["i", $id]);
  }

  public function add($paramsArray)
  {
    $title = $paramsArray['title'];
    $content = $paramsArray['content'];
    $type = $paramsArray['type'];
    $id_developer = $paramsArray['id_developer'];
    $id_programming_language = $paramsArray['id_programming_language'];
    $now = date('Y-m-d H:i:s');
    $item_id = $this->insert(
      "INSERT INTO item (title, description, created_at) VALUES (?, ?, ?)",
      ["sss", $title, $content, $now]
    );
    $lastInsertId = $this->insert(
      "INSERT INTO note (id_item, type, id_developer, id_programming_language) VALUES (?, ?, ?, ?)",
      ["isii", $item_id, $type, 1, 1]
    );
    print($lastInsertId);

    $query = $this->$baseQuery . <<<SQL
    WHERE item.id_item = ?
    SQL;
    return $this->selectOne($query, ["i", $lastInsertId]);
  }

  public function modify($paramsArray)
  {
    $id = $paramsArray['id'];
    $title = $paramsArray['title'];
    $content = $paramsArray['content'];
    $type = $paramsArray['type'];
    return $this->update(
      'UPDATE note SET title = ?, content = ?, type = ? WHERE id_note = ?',
      ["sssi", $title, $content, $type, $id]
    );
  }
}