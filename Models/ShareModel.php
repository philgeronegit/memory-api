<?php
require_once PROJECT_ROOT_PATH . "/Models/Database.php";
require_once PROJECT_ROOT_PATH . "/Models/IModel.php";

class ShareModel extends Database implements IModel
{
  public function __construct()
  {
    parent::__construct();

    $this->baseQuery = <<<SQL
      SELECT
        shared.id_user,
        shared.id_item,
        user.username,
        user.email,
        item.title as note_title
      FROM
        shared
      JOIN
        user ON user.id_user = shared.id_user
      JOIN
        item ON item.id_item = shared.id_item

      SQL;
  }

  public function getAll($args)
  {
    $limit = $args['limit'];
    if (array_key_exists('id', $args)) {
      $id = $args['id'];
      $query = $this->baseQuery . <<<SQL
      WHERE shared.id_item = ?
      ORDER BY user.username ASC
      LIMIT ?
      SQL;

      return $this->select($query, ["ii", $id, $limit]);
    }

    return $this->select($this->baseQuery . " ORDER BY user.username ASC LIMIT ?", ["i", $limit]);
  }

  public function getOne($id, $args = null)
  {
    return (object)[];
  }

  public function remove($id)
  {
    return true;
  }

  public function add($paramsArray)
  {
    $id_user = $paramsArray['id_user'];
    $id_item = $paramsArray['id_item'];

    $this->insert(
      "INSERT INTO shared (id_user, id_item) VALUES (?, ?)",
      ["ii", $id_user, $id_item]
    );

    return true;
  }

  public function modify($paramsArray)
  {
     return true;
  }
}