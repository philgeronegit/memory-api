<?php
require_once PROJECT_ROOT_PATH . "/Models/Database.php";
require_once PROJECT_ROOT_PATH . "/Models/IModel.php";

class MessageModel extends Database implements IModel
{
  public function __construct()
  {
    parent::__construct();

    $this->baseQuery = <<<SQL
        SELECT
            m.id_message,
            m.created_at,
            text,
            GROUP_CONCAT(user.username) AS users,
            GROUP_CONCAT(user.id_user) AS id_users
        FROM
            messages msgs
        RIGHT JOIN
            message m ON m.id_message = msgs.id_message
        LEFT JOIN
            user ON user.id_user = msgs.id_user
        GROUP BY id_message

      SQL;
  }

  public function getAll($args)
  {
    $limit = $args['limit'];
    if (array_key_exists('id', $args)) {
      $id = $args['id'];
      $query = <<<SQL
        SELECT
            m.id_message,
            m.created_at,
            text
        FROM
            messages msgs
                RIGHT JOIN
            message m ON m.id_message = msgs.id_message
                LEFT JOIN
            user ON user.id_user = msgs.id_user
        WHERE msgs.id_user = ?
        ORDER BY created_at DESC LIMIT ?
      SQL;

      return $this->select($query, ["ii", $id, $limit]);
    }

    if (array_key_exists('search', $args)) {
      $search = "%" . $args['search'] . "%";
      $query = $this->baseQuery . <<<SQL
      WHERE
          text LIKE ?
      ORDER BY name ASC LIMIT ?
      SQL;

      return $this->select($query, ["si", $search, $limit]);
    }

    $query = $this->baseQuery . <<<SQL
      ORDER BY created_at DESC LIMIT ?
    SQL;
    return $this->select($query, ["i", $limit]);
  }

  public function getOne($id, $args = null)
  {
    $query = <<<SQL
        SELECT
            id_message,
            created_at,
            text
        FROM
            message
        WHERE id_message = ?
        ORDER BY created_at DESC
    SQL;
    return $this->selectOne($query, ["i", $id]);
  }

  public function remove($id)
  {
    $this->delete("DELETE FROM messages WHERE id_message = ?", ["i", $id]);
    return $this->delete("DELETE FROM message WHERE id_message = ?", ["i", $id]);
  }

  public function add($paramsArray)
  {
    $text = $paramsArray['text'];
    $now = date('Y-m-d H:i:s');
    $id = $this->insert(
      "INSERT INTO message (text, created_at) VALUES (?, ?)",
      ["ss", $text, $now]
    );

    $id_user = $paramsArray['id_user'];
    $this->insert(
      "INSERT INTO messages (id_message, id_user) VALUES (?, ?)",
      ["ii", $id, $id_user]
    );

    $query = $this->baseQuery . <<<SQL
      WHERE m.id_message = ?
    SQL;
    return $this->selectOne($query, ["i", $id]);
  }

  public function addToUser($paramsArray)
  {
    $message_id = $paramsArray['message_id'];
    $user_id = $paramsArray['user_id'];
    return $this->insert(
      "INSERT INTO messages (id_message, id_user) VALUES (?, ?)",
      ["ii", $message_id, $user_id]
    );
  }

  public function modifyForUser($paramsArray)
  {
    $message_id = $paramsArray['message_id'];
    $user_id = $paramsArray['user_id'];

    $query = $this->baseQuery . <<<SQL
    WHERE m.id_message = ? AND id_user = ?
    SQL;
    $message = $this->selectOne($query, ["i", $message_id, $user_id]);

    $read_at = $paramsArray['read_at'] ?? $message->read_at;

    return $this->insert(
      "UPDATE messages SET read_at = ? WHERE id_message = ? AND id_user = ?",
      ["sii", read_at, $message_id, $user_id]
    );
  }

  public function modify($paramsArray)
  {
    $id = $paramsArray['id'];
    $text = $paramsArray['text'];
    $this->update(
      "UPDATE message SET text = ? WHERE id_message = ?",
      ["si", $text, $id]
    );

    $query = $this->baseQuery . <<<SQL
      WHERE m.id_message = ?
    SQL;
    return $this->selectOne($query, ["i", $id]);
  }
}