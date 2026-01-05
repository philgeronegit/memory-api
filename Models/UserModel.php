<?php
require_once PROJECT_ROOT_PATH . "/Models/Database.php";
require_once PROJECT_ROOT_PATH . "/Models/IModel.php";

class UserModel extends Database implements IModel, IValidator
{
  public function __construct()
  {
    parent::__construct();

    $this->baseQuery = <<<SQL
      SELECT
          u.id_user,
          u.username,
          u.email,
          u.avatar_url,
          u.created_at,
          u.id_role,
          r.name as role_name,
          r.role as role_value,
          is_admin
      FROM
          user u
      JOIN
          role r ON r.id_role = u.id_role

      SQL;
  }

  public function getAll($args)
  {
    $limit = $args['limit'];
    $query = $this->baseQuery . <<<SQL
    ORDER BY username ASC
    LIMIT ?
    SQL;

    if (array_key_exists('search', $args)) {
      $search = "%" . $args['search'] . "%";
      $searchType = $args['search_type'] ?? 'all';

      if ($searchType === 'all') {
        $query = $this->baseQuery . <<<SQL
          WHERE
              u.username LIKE ?
          ORDER BY u.username ASC LIMIT ?
        SQL;
      } elseif ($searchType === 'role') {
        $search = $args['search'];
        $query = $this->baseQuery . <<<SQL
          WHERE
              r.id_role = ?
          ORDER BY r.name ASC LIMIT ?
        SQL;
      }


      return $this->select($query, ["si", $search, $limit]);
    }

    if (array_key_exists('id', $args)) {
      $id = $args['id'];
      $query = $this->baseQuery . <<<SQL
        JOIN
          projects p ON p.id_user = u.id_user
        WHERE p.id_project = ?
      SQL;

      return $this->select($query, ["i", $id]);
    }

    return $this->select($query, ["i", $limit]);
  }

  public function getOne($id, $args = null)
  {
    $query = $this->baseQuery . " WHERE id_user = ?";

    return $this->selectOne($query, ["i", $id]);
  }

  public function remove($id)
  {
    $logger = SecurityLogger::getInstance();

    // Get user info before deletion for logging
    $query = $this->baseQuery . " WHERE id_user = ?";
    $user = $this->selectOne($query, ["i", $id]);
    $username = $user->username ?? 'unknown';

    // Get current user for audit trail
    $currentUser = $GLOBALS['jwt_user_data'] ?? null;
    $deletedBy = $currentUser->id_user ?? 0;

    // Begin transaction to ensure data consistency
    $this->connection->begin_transaction();

    try {
      // Delete all tasks associated with the user
      $taskIds = $this->select("SELECT id_item FROM task WHERE id_developer = ?", ["i", $id]);
      $this->delete("DELETE FROM task WHERE id_developer = ?", ["i", $id]);
      foreach ($taskIds as $task) {
        $id_item = $task['id_item'];
        $this->delete("DELETE FROM item WHERE id_item = ?", ["i", $id_item]);
      }

      // Delete all messages associated with the user by querying messages and returning message ids
      $messageIds = $this->select("SELECT id_message FROM messages WHERE id_user = ?", ["i", $id]);
      $this->delete("DELETE FROM messages WHERE id_user = ?", ["i", $id]);
      foreach ($messageIds as $message) {
        $id_message = $message['id_message'];
        $this->delete("DELETE FROM message WHERE id_message = ?", ["i", $id_message]);
      }

      // Delete user from other related tables
      $this->delete("DELETE FROM executive WHERE id_user = ?", ["i", $id]);
      $this->delete("DELETE FROM project_manager WHERE id_user = ?", ["i", $id]);
      $this->delete("DELETE FROM developer WHERE id_user = ?", ["i", $id]);

      // Finally, delete the user
      $this->delete("DELETE FROM user WHERE id_user = ?", ["i", $id]);

      $this->connection->commit();

      // Log successful deletion
      $logger->logUserDeletion($id, $username, $deletedBy);

      return true;
    } catch (Exception $e) {
      $this->connection->rollback();
      throw new Exception("Error deleting user: " . $e->getMessage());
    }
  }

  public function add($paramsArray)
  {
    $logger = SecurityLogger::getInstance();
    $currentUser = $GLOBALS['jwt_user_data'] ?? null;
    $createdBy = $currentUser->id_user ?? null;

    $username = $paramsArray['username'];
    $email = $paramsArray['email'];
    $avatar_url = $paramsArray['avatar_url'];
    $id_role = $paramsArray['id_role'];
    $is_admin = $paramsArray['is_admin'];
    $password = $paramsArray['password'];
    $now = date('Y-m-d H:i:s');
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $id = $this->insert(
      "INSERT INTO user (username, email, avatar_url, id_role, is_admin, password, created_at) " .
      "VALUES (?, ?, ?, ?, ?, ?, ?)",
      ["sssiiss", $username, $email, $avatar_url, $id_role, $is_admin, $hashed_password, $now]
    );

    $searchRoleQuery = <<<SQL
      SELECT role
      FROM role
      WHERE id_role = ?
    SQL;
    $role = $this->selectOne($searchRoleQuery, ["i", $id_role]);
    if ($role->role === 'projectManager') {
      $this->insert(
        "INSERT INTO executive (id_user) VALUES (?)",
        ["i", $id]
      );
      $this->insert(
        "INSERT INTO project_manager (id_user) VALUES (?)",
        ["i", $id]
      );
    }

    $query = $this->baseQuery . <<<SQL
    WHERE id_user = ?
    SQL;

    // Log user creation
    $logger->logUserCreation($id, $username, $createdBy);

    return $this->selectOne($query, ["i", $id]);
  }

  public function modify($paramsArray)
  {
    $logger = SecurityLogger::getInstance();
    $currentUser = $GLOBALS['jwt_user_data'] ?? null;
    $changedBy = $currentUser->id_user ?? 0;

    $id = $paramsArray['id'];

    $query = $this->baseQuery . <<<SQL
    WHERE id_user = ?
    SQL;
    $user = $this->selectOne($query, ["i", $id]);

    // check for empty object ie no user found
    if (empty((array)$user)) {
      throw new Exception("User not found");
    }

    $username = $paramsArray['username'] ?? $user->username;
    $email = $paramsArray['email'] ?? $user->email;
    $avatar_url = $paramsArray['avatar_url'] ?? $user->avatar_url;
    $id_role = $paramsArray['id_role'] ?? $user->id_role;
    $is_admin = $paramsArray['is_admin'] ?? $user->is_admin;

    // Check if role is being changed
    if (isset($paramsArray['id_role']) && $paramsArray['id_role'] != $user->id_role) {
      $logger->logRoleChange(
        $id,
        $user->username,
        $user->role_value ?? 'unknown',
        $paramsArray['id_role'],
        $changedBy
      );
    }

    $this->update(
      "UPDATE user SET username = ?, email = ?, avatar_url = ?, id_role = ?, is_admin = ? " .
      "WHERE user.id_user = ?",
      ["sssiii", $username, $email, $avatar_url, $id_role, $is_admin, $id]
    );

    return $this->selectOne($query, ["i", $id]);
  }

  public function validate($paramsArray): array
  {
    $errors = [
      'hasErrors' => false
    ];

    // Ensure required fields are present
    $requiredFields = ['username', 'email', 'id_role', 'password'];
    $errorMessage = 'The following fields are required: ';
    foreach ($requiredFields as $field) {
      if (empty($paramsArray[$field])) {
          $errorMessage .= $field . ' ';
      }
    }

    if ($errorMessage !== 'The following fields are required: ') {
      $errors['hasErrors'] = true;
      $errors['error'] = $errorMessage;
      $errors['httpHeader'] = array('HTTP/1.1 400 Bad Request');

      return $errors;
    }

    // Check if email already exists to prevent duplicates
    $query = <<<SQL
      SELECT id_user
      FROM user
      WHERE email = ?
    SQL;
    $existingUser = $this->selectOne($query, ["s", $paramsArray['email']]);
    if ($existingUser && isset($existingUser->id_user)) {
      $errors['hasErrors'] = true;
      $errors['error'] = 'Email already exists';
      $errors['httpHeader'] = array('HTTP/1.1 409 Email already exists');
    }

    return $errors;
  }

  public function validateUpdate($paramsArray): array
  {
    $errors = [
      'hasErrors' => false
    ];

    // Ensure required fields are present
    $requiredFields = ['id'];
    $errorMessage = 'The following fields are required: ';
    foreach ($requiredFields as $field) {
      if (empty($paramsArray[$field])) {
          $errorMessage .= $field . ' ';
      }
    }

    if ($errorMessage !== 'The following fields are required: ') {
      $errors['hasErrors'] = true;
      $errors['error'] = $errorMessage;
      $errors['httpHeader'] = array('HTTP/1.1 400 Bad Request');

      return $errors;
    }

    // Check if user exists
    $query = <<<SQL
      SELECT id_user
      FROM user
      WHERE id_user = ?
    SQL;
    $existingUser = $this->selectOne($query, ["i", $paramsArray['id']]);
    if (empty((array)$existingUser)) {
      $errors['hasErrors'] = true;
      $errors['error'] = 'User not found';
      $errors['httpHeader'] = array('HTTP/1.1 404 User not found');
    }

    return $errors;
  }
}