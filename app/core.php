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
    $user = self::get_user($id);
    $user->fullname = trim($fullname);
    DB::update_user($user);
  }

  static function toggle_user_suspend($id)
  {
    $user = self::get_user($id);
    $user->suspended = !$user->suspended;
    DB::update_user($user);
  }

  static function delete_user($id)
  {
    DB::delete_user(self::get_user($id));
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

  static function create_group($name, $description)
  {
    $group = new Group(array(
      'name' => trim($name),
      'description' => $description
    ));

    if (DB::find_group_by_name($group->name)) {
      return array(
        $group,
        array('name' => sprintf('Name must be unique: "%s" is already taken', $group->name))
      );
    }
    if (!$group->name) {
      return array(
        $group,
        array('name' => 'Name cannot be blank')
      );
    }

    DB::save_group($group);

    return array($group, array());
  }

  static function update_group($id, $name, $description)
  {
    $group = self::get_group($id);
    $group->description = $description;
    $name = trim($name);

    $name_unique = $name == $group->name || !DB::find_group_by_name($name);
    if (!$name_unique) {
      $group->name = $name;
      return array(
        $group,
        array('name' => sprintf('Name must be unique: "%s" is already taken', $group->name))
      );
    }

    $group->name = $name;
    if (!$group->name) {
      return array(
        $group,
        array('name' => 'Name cannot be blank')
      );
    }

    DB::update_group($group);

    return array($group, array());
  }

  static function list_groups()
  {
    return DB::find_groups();
  }

  static function get_group($id)
  {
    $group = DB::find_group_by_id($id);
    if (!$group) throw new RecordNotFound($id);
    return $group;
  }
}


class RecordNotFound extends Exception
{
  public function __construct($key)
  {
    parent::__construct(sprintf('Record not found: %s', (string)$key));
  }
}







