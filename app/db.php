<?php

class DB
{
  private static $conn;

  public static function connect(array $config)
  {
    self::$conn = new PDO(
      $config['dsn'],
      null,
      null,
      array(
        PDO::ATTR_PERSISTENT => false,// FIXME: true in PRODUCTION
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
      )
    );
  }

  public static function find_users()
  {
    $stmt = self::exec('SELECT * FROM users' );
    $users = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $users[] = new User($row);
    }
    return $users;
  }

  public static function find_users_by_group_id($group_id)
  {
    $stmt = self::exec('SELECT * FROM users WHERE group_id = ?', array($group_id));
    $users = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $users[] = new User($row);
    }
    return $users;
  }

  public static function find_user_by_id($id)
  {
    $stmt = self::exec('SELECT * FROM users WHERE id = ?', array($id));
    $user = null;
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $user = new User($row);
    }
    return $user;
  }

  public static function find_user_by_username($username)
  {
    $stmt = self::exec('SELECT * FROM users WHERE username = ?', array($username));
    $user = null;
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $user = new User($row);
    }
    return $user;
  }

  public static function update_user($user)
  {
    self::exec(
      'UPDATE users SET fullname = :fullname, suspended = :suspended, group_id = :group_id WHERE id = :id',
      array(
        ':fullname' => $user->fullname, 
        ':suspended' => $user->suspended, 
        ':group_id' => $user->group_id,
        ':id' => $user->id
      )
    );
  }

  public static function save_user($user)
  {
    self::exec(
      'INSERT INTO users (id, username, fullname, suspended) VALUES(NULL, :username, :fullname, :suspended)',
      array(
        ':username' => $user->username,
        ':fullname' => $user->fullname,
        ':suspended' => $user->suspended
      )
    );
  }

  public static function delete_user($user)
  {
    self::exec('DELETE FROM users WHERE id = ?', array($user->id));
  }

  public static function find_groups()
  {
    $stmt = self::exec('SELECT * FROM groups' );
    $groups = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $groups[] = new group($row);
    }
    return $groups;
  }

  public static function save_group($group)
  {
    self::exec(
      'INSERT INTO groups (id, name, description) VALUES(NULL, :name, :description)',
      array(
        ':name' => $group->name,
        ':description' => $group->description,
      )
    );
  }

  public static function find_group_by_name($name)
  {
    $stmt = self::exec('SELECT * FROM groups WHERE name = ?', array($name));
    $group = null;
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $group = new Group($row);
    }
    return $group;
  }

  public static function find_group_by_id($id)
  {
    $stmt = self::exec('SELECT * FROM groups WHERE id = ?', array($id));
    $group = null;
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $group = new Group($row);
    }
    return $group;
  }

  public static function update_group($group)
  {
    self::exec(
      'UPDATE groups SET name = :name, description = :description WHERE id = :id',
      array(
        ':name' => $group->name, 
        ':description' => $group->description, 
        ':id' => $group->id
      )
    );
  }

  public static function delete_group($group)
  {
    self::exec('DELETE FROM groups WHERE id = ?', array($group->id));
  }

  public static function save_rule($rule)
  {
    if ($rule instanceof HTTPAccessRule) {
      self::save_http_rule($rule);
      return;
    }

    if ($rule instanceof TCPUDPAccessRule) {

    }

    throw new RuntimeException('Should not reach here');
  }

  public static function find_rules_by_owner_id_and_owner_type($rule_type, $owner_id, $owner_type)
  {
    $stmt = self::exec(<<<SQL
      SELECT * FROM {$rule_type}_access_rules WHERE owner_id = ? AND owner_type = ? 
      ORDER BY position
SQL
    , array($owner_id, $owner_type)
    );

    $rules = array();
    while ($row = $stmt->fetch()) {
      $rules[] = AccessRule::factory($rule_type, $row);
    }

    return $rules;
  }

  public static function save_http_rule($rule)
  {
    if ($rule->id) {
      self::exec(<<<SQL
        UPDATE http_access_rules SET
          address = :address, 
          allow = :allow, 
          position = :position, 
          owner_type = :owner_type, 
          owner_id = :owner_id, 
          http = :http, 
          https = :https
        WHERE id = :id
SQL
      , array(
          ':address' => $rule->address,
          ':allow' => $rule->allow,
          ':position' => $rule->position,
          ':owner_type' => $rule->owner_type,
          ':owner_id' => $rule->owner_id,
          ':http' => $rule->http,
          ':https' => $rule->https,
          ':id' => $rule->id
        )
      );
    } else {
      self::exec(<<<SQL
        INSERT INTO http_access_rules (id, address, allow, position, owner_type, owner_id, http, https) 
        VALUES (NULL, :address, :allow, :position, :owner_type, :owner_id, :http, :https) 
SQL
      , array(
          ':address' => $rule->address,
          ':allow' => $rule->allow,
          ':position' => $rule->position,
          ':owner_type' => $rule->owner_type,
          ':owner_id' => $rule->owner_id,
          ':http' => $rule->http,
          ':https' => $rule->https
        )
      );

      # Fetch new ID
      $query = self::exec('SELECT last_insert_rowid() FROM http_access_rules');
      $rule->id = intval($query->fetchColumn());
    }
  }

  public static function find_rule_by_id($rule_type, $id)
  {
    $stmt = self::exec(
      "SELECT * FROM {$rule_type}_access_rules WHERE id = ?",
      array($id)
    );
    $rule = null;
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $rule = AccessRule::factory($rule_type, $row);
    }
    return $rule;
  }


  private static function exec($sql, $bindings = array())
  {
    $stmt = self::$conn->prepare($sql);
    $stmt->execute($bindings);
    return $stmt;
  }
}
