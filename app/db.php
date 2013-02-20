<?php

class DB
{
  private static $connection;

  public static function connect(array $config)
  {
    self::$connection = new PDO(
      $config['dsn'],
      null,
      null,
      array(
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
      )
    );
  }

  public static function findUsers()
  {
    return array();
  }
}
