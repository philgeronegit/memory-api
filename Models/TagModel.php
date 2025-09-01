<?php
require_once PROJECT_ROOT_PATH . "/Models/Database.php";
require_once PROJECT_ROOT_PATH . "/Models/IModel.php";

class TagModel extends Database implements IModel
{
  public function getAll($args)
  {
    $limit = $args['limit'];
    if (array_key_exists('id', $args)) {
      $id = $args['id'];
      $query = <<<SQL
      SELECT tag.id_tag, tag.name
      FROM tag
      INNER JOIN tags ON tag.id_tag = tags.id_tag
      WHERE tags.id_item = ?
      ORDER BY tag.name ASC LIMIT ?
      SQL;

      return $this->select($query, ["ii", $id, $limit]);
    }

    if (array_key_exists('search', $args)) {
      $search = "%" . $args['search'] . "%";
      $query = <<<SQL
      SELECT * FROM tag
      WHERE
          name LIKE ?
      ORDER BY name ASC LIMIT ?
      SQL;

      return $this->select($query, ["si", $search, $limit]);
    }

    return $this->select("SELECT * FROM tag ORDER BY name ASC LIMIT ?", ["i", $limit]);
  }

  public function getOne($id, $args = null)
  {
    return $this->selectOne("SELECT * FROM tag WHERE id_tag = ?", ["i", $id]);
  }

  public function remove($id)
  {
    return $this->delete("DELETE FROM tag WHERE id_tag = ?", ["i", $id]);
  }

  public function removeToNote($paramsArray)
  {
    $note_id = $paramsArray['note_id'];
    $tag_id = $paramsArray['tag_id'];
    return $this->delete(
      "DELETE FROM tags WHERE id_tag = ? AND id_item = ?",
      ["ii", $tag_id, $note_id]
    );
  }

  public function add($paramsArray)
  {
    $name = $paramsArray['name'];
    $id = $this->insert(
      "INSERT INTO tag (name) VALUES (?)",
      ["s", $name]
    );

    $query = <<<SQL
    SELECT * FROM tag
    WHERE id_tag = ?
    SQL;
    return $this->selectOne($query, ["i", $id]);
  }

  public function addToNote($paramsArray)
  {
    $note_id = $paramsArray['note_id'];
    $tag_ids = $paramsArray['tag_ids'];

    if (!is_array($tag_ids)) {
      throw new InvalidArgumentException('tag_ids must be an array.');
    }

    // delete all tags for the note
    $this->delete("DELETE FROM tags WHERE id_item = ?", ["i", $note_id]);

    // insert new tags
    foreach ($tag_ids as $tag_id) {
      $this->insert(
        "INSERT INTO tags (id_tag, id_item) VALUES (?, ?)",
        ["ii", $tag_id, $note_id]
      );
    }

    return true;
  }

  public function modify($paramsArray)
  {
    $id = $paramsArray['id'];
    $name = $paramsArray['name'];
    return $this->update(
      "UPDATE tag SET name = ? WHERE id_tag = ?",
      ["si", $name, $id]
    );
  }
}