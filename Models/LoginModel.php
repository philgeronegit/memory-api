<?php
require_once PROJECT_ROOT_PATH . "/Models/Database.php";
require_once PROJECT_ROOT_PATH . "/Models/IModel.php";

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class LoginModel extends Database
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
          u.password,
          u.id_role,
          u.refresh_token,
          r.name as role_name,
          r.role as role_value,
          u.is_admin
      FROM
          user u
              JOIN
          role r ON r.id_role = u.id_role

      SQL;
  }

  public function add($paramsArray)
  {
    $username = $paramsArray['username'];
    $password = $paramsArray['password'];

    // Fetch user by username only
    $query = $this->baseQuery . " WHERE u.username = ?";

    $user = $this->selectOne($query, ["s", $username]);
    $isPasswordValid = false;
    if ($user) {
      // Check for encoding issues
      $isPasswordValid = password_verify($password, $user->password);
    }
    if ($user && $isPasswordValid) {
      // Remove password from result before returning
      unset($user->password);
      // Generate JWT token
      $jwtToken = $this->generateJwtToken($user);
      // Generate refresh token
      $refreshToken = $this->generateRefreshToken($user);
      // Update user with refresh token
      $this->update("UPDATE user SET refresh_token = ? WHERE id_user = ?", ["si", $refreshToken, $user->id_user]);
      // Add tokens to user object
      $user->access_token = $jwtToken;
      $user->refresh_token = $refreshToken;
      $user->token_type = 'Bearer';
      $user->expires_in = (int)JWT_EXPIRATION_TIME;
      return $user;
    }
    return null;
  }

  private function generateJwtToken($user)
  {
    $secretKey = JWT_SECRET_KEY;
    $issuedAt = time();
    $expirationTime = $issuedAt + JWT_EXPIRATION_TIME; // Token expiration time
    $payload = [
      'iat' => $issuedAt, // Issued at: time when the token was generated
      'exp' => $expirationTime, // Expiration time
      'iss' => JWT_ISSUER, // Issuer
      'aud' => JWT_AUDIENCE, // Audience
      'data' => [ // Data related to the logged-in user
        'id_user' => $user->id_user,
        'username' => $user->username,
        'role' => $user->role_value,
      ]
    ];
    $algo = JWT_ALGORITHM;

    try {
      return JWT::encode($payload, $secretKey, $algo);
    } catch (Exception $e) {
      error_log("Error generating JWT: " . $e->getMessage());
      return null;
    }
  }

  private function generateRefreshToken($user)
  {
    $secretKey = JWT_SECRET_KEY;
    $issuedAt = time();
    $expirationTime = $issuedAt + JWT_REFRESH_EXPIRATION_TIME; // Refresh token expiration time
    $payload = [
      'iat' => $issuedAt, // Issued at: time when the token was generated
      'exp' => $expirationTime, // Expiration time
      'iss' => JWT_ISSUER, // Issuer
      'aud' => JWT_AUDIENCE, // Audience
      'data' => [ // Data related to the logged-in user
        'id_user' => $user->id_user,
        'username' => $user->username,
        'type' => 'refresh',
      ]
    ];
    $algo = JWT_ALGORITHM;

    try {
      return JWT::encode($payload, $secretKey, $algo);
    } catch (Exception $e) {
      error_log("Error generating refresh JWT: " . $e->getMessage());
      return null;
    }
  }

  public function refreshAccessToken($refreshToken)
  {
    try {
      $decoded = JWT::decode($refreshToken, new Key(JWT_SECRET_KEY, JWT_ALGORITHM));
      if ($decoded->data->type !== 'refresh') {
        return null; // Not a refresh token
      }
      $userId = $decoded->data->id_user;
      // Fetch user
      $query = $this->baseQuery . " WHERE u.id_user = ? AND u.refresh_token = ?";
      $user = $this->selectOne($query, ["is", $userId, $refreshToken]);
      if ($user) {
        // Generate new access token
        $newAccessToken = $this->generateJwtToken($user);
        // Optionally generate new refresh token
        $newRefreshToken = $this->generateRefreshToken($user);
        // Update DB with new refresh token
        $this->update("UPDATE user SET refresh_token = ? WHERE id_user = ?", ["si", $newRefreshToken, $user->id_user]);
        // Remove password
        unset($user->password);
        // Return user with new tokens
        $user->access_token = $newAccessToken;
        $user->refresh_token = $newRefreshToken;
        $user->token_type = 'Bearer';
        $user->expires_in = (int)JWT_EXPIRATION_TIME;
        return $user;
      }
    } catch (Exception $e) {
      error_log("Error refreshing token: " . $e->getMessage());
    }
    return null;
  }

  public function modify($paramsArray)
  {
    $id = $paramsArray['id'];

    $query = $this->baseQuery . <<<SQL
    WHERE id_user = ?
    SQL;

    $password = $paramsArray['password'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $this->update(
      "UPDATE user SET password = ? WHERE id_user = ?",
      ["si", $hashed_password, $id]
    );

    return $this->selectOne($query, ["i", $id]);
  }
}