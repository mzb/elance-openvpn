<?php

require 'models.php';
require 'db.php';
require '../lib/OpenVPN.php';


class Core
{
  static function init($config)
  {
    DB::connect($config);
  }

  static function create_user($username, $fullname)
  {
    $user = new User(array(
      'username' => trim($username),
      'fullname' => trim($fullname)
    ));

    if (DB::find_user_by_username($user->username)) {
      return array(
        $user,
        array('username' => sprintf('Username must be unique: "%s" is already taken', $user->username))
      );
    }
    if (!$user->username) {
      return array(
        $user,
        array('username' => 'Username cannot be blank')
      );
    }

    DB::save_user($user);

    return array($user, array());
  }

  static function update_user($id, $fullname)
  {
    $user = DB::find_user_by_id($id);
    if (!$user) throw new RecordNotFound($id);

    $user->fullname = trim($fullname);
    DB::update_user($user);
  }

  static function toggle_user_suspend($id)
  {
    $user = DB::find_user_by_id($id);
    if (!$user) throw new RecordNotFound($id);
    $user->suspended = !$user->suspended;
    DB::update_user($user);
  }

  static function delete_user($id)
  {
    $user = DB::find_user_by_id($id);
    if (!$user) throw new RecordNotFound($id);
    DB::delete_user($user);
  }

  static function list_users()
  {
    return DB::find_users();
  }

  static function get_user($id)
  {
    $user = DB::find_user_by_id($id);
    if (!$user) throw new RecordNotFound($id);

    return $user;
  }

  static function list_groups()
  {
    return DB::find_groups();
  }
}


class RecordNotFound extends Exception
{
  public function __construct($key)
  {
    parent::__construct(sprintf('Record not found: %s', (string)$key));
  }
}







