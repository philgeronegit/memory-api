<?php
require_once PROJECT_ROOT_PATH . "/Models/Database.php";
require_once PROJECT_ROOT_PATH . "/Models/IModel.php";

class RoleModel extends Database implements IModel
{
  public function __construct()
  {
    parent::__construct();

    $this->baseQuery = <<<SQL
      SELECT
        role.id_role,
        role.name,
        role.role,
        group_concat(permission.name) as permissions,
        CONCAT('[',
          group_concat(
            CONCAT(
              '{"id_permission":', COALESCE(permission.id_permission, 'null'),
              ',"name":"', COALESCE(permission.name, ''), '"}'
            )
          ),
        ']') as permissions_json
      FROM role
      LEFT JOIN permissions ON role.id_role = permissions.id_role
      LEFT JOIN permission ON permissions.id_permission = permission.id_permission
      GROUP BY role.id_role, role.name, role.role

    SQL;
  }

  public function getAll($args)
  {
    $limit = $args['limit'];

    if (array_key_exists('search', $args)) {
      $search = "%" . $args['search'] . "%";
      $query = <<<SQL
        SELECT
          id_role,
          name,
          role
        FROM role
        WHERE name LIKE ?
        ORDER BY name ASC LIMIT ?
      SQL;

      return $this->select($query, ["si", $search, $limit]);
    }

    $query = $this->baseQuery . <<<SQL
      ORDER BY name ASC
      LIMIT ?
    SQL;
    return $this->select($query, ["i", $limit]);
  }

  public function getOne($id, $args = null)
  {
    return $this->selectOne("SELECT * FROM role WHERE id_role = ?", ["i", $id]);
  }

  public function remove($id)
  {
    return $this->delete("DELETE FROM role WHERE id_role = ?", ["i", $id]);
  }

  public function add($paramsArray)
  {
    $name = $paramsArray['name'];
    return $this->insert(
      "INSERT INTO role (name) VALUES (?)",
      ["s", $name]
    );
  }

  public function modify($paramsArray)
  {
    $id = $paramsArray['id'];
    $name = $paramsArray['name'];
    return $this->update(
      "UPDATE role SET name = ? WHERE id_role = ?",
      ["si", $name, $id]
    );
  }

  public function getPermissionsByRole($id_role)
  {
    $query = <<<SQL
    SELECT permission.id_permission, permission.name
    FROM permission
    INNER JOIN permissions ON permission.id_permission = permissions.id_permission
    WHERE permissions.id_role = ?
    ORDER BY permission.name ASC
    SQL;

    return $this->select($query, ["i", $id_role]);
  }
}