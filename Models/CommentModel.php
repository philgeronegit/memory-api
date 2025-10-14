<?php
require_once PROJECT_ROOT_PATH . "/Models/Database.php";
require_once PROJECT_ROOT_PATH . "/Models/IModel.php";

class CommentModel extends Database implements IModel
{
  public function __construct()
  {
    parent::__construct();

    $this->baseQuery = <<<SQL
      SELECT
        c.id_comment,
        c.content,
        c.created_at,
        c.updated_at,
        user.id_user,
        user.username,
        user.email,
        IFNULL(cs.score, 0) AS score,
        COALESCE(like_counts.total_likes, 0) AS total_likes,
        COALESCE(dislike_counts.total_dislikes, 0) AS total_dislikes
      FROM
        comment AS c
      JOIN
        note ON note.id_item = c.id_item
      JOIN
        user ON user.id_user = c.id_user
      LEFT JOIN
        comment_scores cs ON cs.id_user = c.id_user
        AND cs.id_comment = c.id_comment
      LEFT JOIN
        (SELECT
          id_comment,
          COUNT(*) AS total_likes
        FROM
          comment_scores
        WHERE
          score = 1
        GROUP BY
          id_comment) AS like_counts
        ON like_counts.id_comment = c.id_comment
      LEFT JOIN
        (SELECT
          id_comment,
          COUNT(*) AS total_dislikes
        FROM
          comment_scores
        WHERE
          score = -1
        GROUP BY
          id_comment) AS dislike_counts
        ON dislike_counts.id_comment = c.id_comment

      SQL;
  }

  public function getAll($args)
  {
    $limit = $args['limit'];
    if (array_key_exists('id', $args)) {
      $id = $args['id'];
      $query = $this->baseQuery . <<<SQL
      WHERE
          c.id_item = ?
      ORDER BY id_comment ASC LIMIT ?
      SQL;

      return $this->select($query, ["ii", $id, $limit]);
    }

    $query = $this->baseQuery . <<<SQL
    ORDER BY id_comment ASC LIMIT ?
    SQL;

    return $this->select($query, ["i", $limit]);
  }

  public function getOne($id, $args = null)
  {
    $query = $this->baseQuery . " WHERE c.id_comment = ?";

    return $this->selectOne($query, ["i", $id]);
  }

  public function remove($id)
  {
    return $this->delete("DELETE FROM comment WHERE id_comment = ?", ["i", $id]);
  }

  public function add($paramsArray)
  {
    $content = $paramsArray['content'];
    $id_user = $paramsArray['id_user'];
    $id_item = $paramsArray['id_item'];
    $now = date('Y-m-d H:i:s');

    $this->insert(
      "INSERT INTO comment (content, created_at,id_user, id_item) VALUES (?, ?, ?, ?)",
      ["ssii", $content, $now, $id_user, $id_item]
    );

    $id = $this->insert(
      "INSERT INTO comment_scores (id_comment, id_user) VALUES (?, ?)",
      ["ii", $id_item, $id_user]
    );

    $query = $this->baseQuery . <<<SQL
    WHERE id_comment = ?
    SQL;
    return $this->selectOne($query, ["i", $id]);
  }

  public function modify($paramsArray)
  {
    $id = $paramsArray['id'];
    $user_id = $paramsArray['user_id'];

    $query = $this->baseQuery . <<<SQL
    WHERE id_comment = ?
    SQL;
    $comment = $this->selectOne($query, ["i", $id]);

    if ($user_id) {
      $query = <<<SQL
      SELECT * FROM comment_scores
      WHERE id_comment = ? AND id_user = ?
      SQL;
      $comment_like = $this->selectOne($query, ["ii", $id, $user_id]);
      $liked_at = $paramsArray['liked_at'] ?? $comment_like->liked_at;
      $disliked_at = $paramsArray['disliked_at'] ?? $comment_like->disliked_at;

      $this->update(
        <<<SQL
        UPDATE comment_scores
        SET liked_at = ?,
            disliked_at = ?
        WHERE id_comment = ? AND id_user = ?
        SQL,
        ["ssii", $paramsArray['liked_at'], $paramsArray['disliked_at'], $id, $user_id]
      );
    }

    $content = $paramsArray['content'] ;
    $like_count = $paramsArray['like_count'] ?? $comment->like_count;
    $dislike_count = $paramsArray['dislike_count'] ?? $comment->dislike_count;
    $now = date('Y-m-d H:i:s');

    return $this->update(
      <<<SQL
      UPDATE comment
      SET content = ?,
        updated_at = ?,
        like_count = ?,
        dislike_count = ?
      WHERE id_comment = ?
      SQL,
      ["ssiii", $content, $now, $like_count, $dislike_count, $id]
    );
  }
}