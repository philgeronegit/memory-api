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
        project.name AS project_name,
        note.id_user,
        user.username,
        user.email,
        (SELECT group_concat(tag.name) FROM tags JOIN tag ON tag.id_tag = tags.id_tag WHERE tags.id_item = item.id_item) as tags,
        COALESCE(like_counts.total_likes, 0) AS total_likes,
        COALESCE(dislike_counts.total_dislikes, 0) AS total_dislikes
      FROM item
      JOIN
        note ON note.id_item = item.id_item
      JOIN
        user ON user.id_user = note.id_user
      JOIN
        project ON project.id_project = note.id_project
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
    if (array_key_exists('id', $args)) {
      $id = $args['id'];
      $count = $args['count'] ?? false;
      if ($count) {
        $query = <<<SQL
          SELECT
              DATE_FORMAT(created_at, '%Y-%m') AS month,
              CASE
                  WHEN MONTH(created_at) = 1 THEN 'Janvier'
                  WHEN MONTH(created_at) = 2 THEN 'Février'
                  WHEN MONTH(created_at) = 3 THEN 'Mars'
                  WHEN MONTH(created_at) = 4 THEN 'Avril'
                  WHEN MONTH(created_at) = 5 THEN 'Mai'
                  WHEN MONTH(created_at) = 6 THEN 'Juin'
                  WHEN MONTH(created_at) = 7 THEN 'Juillet'
                  WHEN MONTH(created_at) = 8 THEN 'Août'
                  WHEN MONTH(created_at) = 9 THEN 'Septembre'
                  WHEN MONTH(created_at) = 10 THEN 'Octobre'
                  WHEN MONTH(created_at) = 11 THEN 'Novembre'
                  WHEN MONTH(created_at) = 12 THEN 'Décembre'
              END AS month_name,
              COUNT(*) AS item_count
          FROM
              item
                  JOIN
              note ON note.id_item = item.id_item
          WHERE
              id_user = ?
          GROUP BY DATE_FORMAT(created_at, '%Y-%m')
          ORDER BY month DESC
          LIMIT 6
        SQL;
        return $this->select($query, ["i", $id]);
      }

      $query = <<<SQL
        WITH user_projects AS (
          -- Get all project IDs that the user is a member of
          SELECT id_project
          FROM projects
          WHERE id_user = ?
        ),
        user_items AS (
          -- Get all item IDs that the user owns
          SELECT id_item, 'owned' AS access_type
          FROM note
          WHERE id_user = ?

          UNION

          -- Get all item IDs that are shared with the user
          SELECT id_item, 'shared' AS access_type
          FROM shared
          WHERE id_user = ?

          UNION

          -- Get all item IDs from projects the user is a member of
          SELECT note.id_item, 'project_member' AS access_type
          FROM note
          JOIN user_projects ON user_projects.id_project = note.id_project
          WHERE note.id_user != ?
        )
        SELECT
          item.id_item AS id_note,
          item.title,
          item.description AS content,
          note.type,
          note.is_public,
          item.created_at,
          note.id_project,
          project.name AS project_name,
          note.id_user,
          user.username,
          user_items.access_type
        FROM user_items
        JOIN item ON item.id_item = user_items.id_item
        JOIN note ON note.id_item = item.id_item
        JOIN user ON user.id_user = note.id_user
        JOIN project ON project.id_project = note.id_project
        WHERE note.is_public = true OR note.id_user = ?
        ORDER BY created_at DESC LIMIT ?
      SQL;

      return $this->select($query, ["iiiiii", $id, $id, $id, $id, $id, $limit]);
    }

    if (array_key_exists('search', $args)) {
      $search = "%" . $args['search'] . "%";
      $query = $this->baseQuery . <<<SQL
      WHERE
          item.title LIKE ? OR item.description LIKE ?
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
    $now = date('Y-m-d H:i:s');
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
    $id_project = $paramsArray['id_project'] ?? $note->id_project;

    $this->update(
      'UPDATE item SET title = ?, description = ?, updated_at = ? WHERE id_item = ?',
      ["sssi", $title, $content, $now, $id]
    );
    $this->update(
      'UPDATE note SET is_public = ?, id_project = ? WHERE id_item = ?',
      ["iii", $is_public, $id_project, $id]
    );

    return $this->selectOne($query, ["i", $id]);
  }
}