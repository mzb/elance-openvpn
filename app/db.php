<?php

require 'models.php';


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

  public static function findUsers()
  {
    $stmt = self::exec('SELECT * FROM users' );
    $users = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $users[] = new User($row);
    }
    return $users;
  }

  public function findUserById($id)
  {
    $stmt = self::exec('SELECT * FROM users WHERE id = ?', array($id));
    $user = null;
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $user = new User($row);
    }
    return $user;
  }

  public function findUserByUsername($username)
  {
    $stmt = self::exec('SELECT * FROM users WHERE username = ?', array($username));
    $user = null;
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $user = new User($row);
    }
    return $user;
  }

  public function updateUser($user)
  {
    self::exec(
      'UPDATE users SET fullname = :fullname, suspended = :suspended WHERE id = :id',
      array(
        ':fullname' => $user->fullname, 
        ':suspended' => $user->suspended, 
        ':id' => $user->id)
    );
  }

  public function saveUser($user)
  {
    if (self::findUserByUsername($user->username)) {
      return array('username' => sprintf('Username must be unique: "%s" is already taken', $user->username));
    }
    if (!trim($user->username)) {
      return array('username' => 'Username cannot be blank');
    }

    self::exec(
      'INSERT INTO users (id, username, fullname, suspended) VALUES(NULL, :username, :fullname, :suspended)',
      array(
        ':username' => $user->username,
        ':fullname' => $user->fullname,
        ':suspended' => $user->suspended
      )
    );

    return array();
  }

  public function deleteUser($user)
  {
    self::exec('DELETE FROM users WHERE id = ?', array($user->id));
  }


  private static function exec($sql, $bindings = array())
  {
    $stmt = self::$conn->prepare($sql);
    $stmt->execute($bindings);
    return $stmt;
  }
}