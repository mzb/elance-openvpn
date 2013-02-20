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
      'UPDATE users SET fullname = :fullname, suspended = :suspended WHERE id = :id',
      array(
        ':fullname' => $user->fullname, 
        ':suspended' => $user->suspended, 
        ':id' => $user->id)
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


  private static function exec($sql, $bindings = array())
  {
    $stmt = self::$conn->prepare($sql);
    $stmt->execute($bindings);
    return $stmt;
  }
}
