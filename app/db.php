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

  public function updateUser($user)
  {
    self::exec(
      'UPDATE users SET fullname = ?, suspended = ? WHERE id = ?',
      array($user->fullname, intval($user->suspended), $user->id)
    );
  }

  private static function exec($sql, $bindings = array())
  {
    $stmt = self::$conn->prepare($sql);
    $stmt->execute($bindings);
    return $stmt;
  }
}
