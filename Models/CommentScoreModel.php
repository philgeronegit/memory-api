<?php
require_once PROJECT_ROOT_PATH . "/Models/Database.php";
require_once PROJECT_ROOT_PATH . "/Models/IModel.php";

class CommentScoreModel extends Database implements IModel
{
  public function __construct()
  {
    parent::__construct();

    $this->baseQuery = <<<SQL
      SELECT
          *
      FROM
          comment_scores

      SQL;
  }

  public function getAll($args)
  {
    $limit = $args['limit'];

    $query = $this->baseQuery . <<<SQL
      LIMIT ?
    SQL;

    return $this->select($query, ["i", $limit]);
  }

  public function getOne($id, $args = null)
  {
    $query = $this->baseQuery . " WHERE id_item = ?";

    return $this->selectOne($query, ["i", $id]);
  }

  public function remove($id)
  {
    return $this->delete("DELETE FROM comment_scores WHERE id_item = ?", ["i", $id]);
  }

  public function modify($paramsArray)
  {
    $id = $paramsArray['id'];
    $user_id = $paramsArray['user_id'];
    $score = $paramsArray['score'];

    // check if the score already exists
    $query = $this->baseQuery . <<<SQL
    WHERE id_item = ? AND id_user = ?
    SQL;
    $existingScore = $this->selectOne($query, ["ii", $id, $user_id]);
    // check is empty object
    if (is_object($existingScore) && empty((array)$existingScore)) {
      // if it doesn't exist, insert a new score
      $this->insert(
        "INSERT INTO comment_scores (id_item, id_user, score) VALUES (?, ?, ?)",
        ["iii", $id, $user_id, $score]
      );
    } else {
      // if it exists, update the score
      $this->update(
        "UPDATE comment_scores SET score = ? WHERE id_item = ? AND id_user = ?",
        ["iii", $score, $id, $user_id]
      );
    }

    $query = $this->baseQuery . <<<SQL
    WHERE id_item = ? AND id_user = ?
    SQL;
    return $this->selectOne($query, ["ii", $id, $user_id]);
  }

  public function add($paramsArray)
  {
    $note_id = $paramsArray['note_id'];
    $user_id = $paramsArray['user_id'];
    $score = $paramsArray['score'];
    $this->insert(
      "INSERT INTO comment_scores (id_item, id_user, score) VALUES (?, ?, ?)",
      ["iii", $note_id, $user_id, $score]
    );
    $query = $this->baseQuery . <<<SQL
    WHERE id_item = ? AND id_user = ?
    SQL;
    return $this->selectOne($query, ["ii", $note_id, $user_id]);
  }
}