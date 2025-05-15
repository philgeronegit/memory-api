<?php
require_once PROJECT_ROOT_PATH . "/Models/Database.php";
require_once PROJECT_ROOT_PATH . "/Models/IModel.php";

class NoteModel extends Database implements IModel
{
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
        note.id_user,
        user.username,
        user.email,
        COALESCE(like_counts.total_likes, 0) AS total_likes,
        COALESCE(dislike_counts.total_dislikes, 0) AS total_dislikes
      FROM item
      JOIN
        note ON note.id_item = item.id_item
      JOIN
        user ON user.id_user = note.id_user
      LEFT JOIN
        (SELECT
            id_item, COUNT(*) AS total_likes
        FROM
            note_scores
        WHERE
            score = 1
        GROUP BY id_item) AS like_counts ON like_counts.id_item = item.id_item
            LEFT JOIN
        (SELECT
            id_item, COUNT(*) AS total_dislikes
        FROM
            note_scores
        WHERE
            score = - 1
        GROUP BY id_item) AS dislike_counts ON dislike_counts.id_item = item.id_item

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

  public function getOne($id, $args = null)
  {
    $user_id = $args['user_id'] ?? null;
    if ($user_id) {
      $query = <<<SQL
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
          note.id_user,
          user.username,
          user.email,
          note_scores.score
        FROM item
        JOIN note ON note.id_item = item.id_item
        JOIN user ON user.id_user = note.id_user
        LEFT JOIN note_scores ON note_scores.id_item = item.id_item AND note_scores.id_user = ?
        WHERE item.id_item = ?
      SQL;
      return $this->selectOne($query, ["ii", $user_id, $id]);
    }

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
    $id_user = $paramsArray['id_user'];
    $id_project = $paramsArray['id_project'];
    $is_public = $paramsArray['is_public'];
    $id_programming_language = $paramsArray['id_programming_language'];
    $now = date('Y-m-d H:i:s');
    $item_id = $this->insert(
      "INSERT INTO item (title, description, created_at) VALUES (?, ?, ?)",
      ["sss", $title, $content, $now]
    );
    $this->insert(
      "INSERT INTO note (id_item, type, is_public, id_user, id_project, id_programming_language) VALUES (?, ?, ?, ?, ?, ?)",
      ["isiiii", $item_id, $type, $is_public, $id_user, $id_project, $id_programming_language]
    );

    $query = $this->baseQuery . <<<SQL
    WHERE item.id_item = ?
    SQL;
    return $this->selectOne($query, ["i", $item_id]);
  }

  public function modify($paramsArray)
  {
    $id = $paramsArray['id'];
    $user_id = $paramsArray['user_id'];

    $query = $this->baseQuery . <<<SQL
    WHERE item.id_item = ?
    SQL;
    $note = $this->selectOne($query, ["i", $id]);

    if ($user_id) {
      $query = <<<SQL
      SELECT * FROM note_scores
      WHERE id_item = ? AND id_user = ?
      SQL;
      $note_score = $this->selectOne($query, ["ii", $id, $user_id]);
      $score = $paramsArray['score'] ?? $note_score->score;

      $this->update(
        <<<SQL
        UPDATE note_scores
        SET score = ?
        WHERE id_item = ? AND id_user = ?
        SQL,
        ["ssii", $paramsArray['score'], $id, $user_id]
      );
    }

    $title = $paramsArray['title'] ?? $note->title;
    $content = $paramsArray['content'] ?? $note->content;
    $is_public = $paramsArray['is_public'] ?? $note->is_public;

    $this->update(
      'UPDATE item SET title = ?, description = ? WHERE id_item = ?',
      ["ssi", $title, $content, $id]
    );
    $this->update(
      'UPDATE note SET is_public = ? WHERE id_item = ?',
      ["ii", $is_public, $id]
    );

    return $this->selectOne($query, ["i", $id]);
  }
}