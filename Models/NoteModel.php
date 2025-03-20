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
        note.id_users
      FROM
        item
      INNER JOIN note ON item.id_item = note.id_item

      SQL;
  }

  public function getAll($args)
  {
    $limit = $args['limit'];
    if (array_key_exists('search', $args)) {
      $search = "%" . $args['search'] . "%";
      $query = $this->baseQuery . <<<SQL
      WHERE
          title LIKE ? OR description LIKE ?
      ORDER BY id_note ASC LIMIT ?
      SQL;

      return $this->select($query, ["ssi", $search, $search, $limit]);
    }

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
    $id_users = $paramsArray['id_users'];
    $id_programming_language = $paramsArray['id_programming_language'];
    $now = date('Y-m-d H:i:s');
    $item_id = $this->insert(
      "INSERT INTO item (title, description, created_at) VALUES (?, ?, ?)",
      ["sss", $title, $content, $now]
    );
    $lastInsertId = $this->insert(
      "INSERT INTO note (id_item, type, id_users, id_programming_language) VALUES (?, ?, ?, ?)",
      ["isii", $item_id, $type, 1, 1]
    );

    $query = $this->baseQuery . <<<SQL
    WHERE item.id_item = ?
    SQL;
    return $this->selectOne($query, ["i", $lastInsertId]);
  }

  public function modify($paramsArray)
  {
    $id = $paramsArray['id'];
    $title = $paramsArray['title'];
    $content = $paramsArray['content'];
    $this->update(
      'UPDATE item SET title = ?, description = ? WHERE id_item = ?',
      ["ssi", $title, $content, $id]
    );

    $query = $this->baseQuery . <<<SQL
    WHERE item.id_item = ?
    SQL;
    return $this->selectOne($query, ["i", $id]);
  }
}