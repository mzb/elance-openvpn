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

  static function get_group_members($group_id)
  {
    return DB::find_users_by_group_id($group_id);
  }

  static function update_user_membership($user_id, $group_id)
  {
    $user = self::get_user($user_id);
    if ($group_id) self::get_group($group_id);

    $user->group_id = $group_id;
    DB::update_user($user);
  }

  static function delete_group($id)
  {
    $group = self::get_group($id);
    DB::delete_group($group);
  }

  static function save_http_rule($owner_type, $owner_id, $http, $https, $allow, $address, $id = null)
  {
    if ($id) {
      $rule = self::get_rule('http', $id);
    }

    $rule = AccessRule::factory('http', array(
      'id' => $id,
      'owner_type' => $owner_type, 
      'owner_id' => $owner_id, 
      'http' => $http, 
      'https' => $https, 
      'allow' => $allow, 
      'address' => $address
    ));

    $errors = array();
    if (!trim($rule->address)) {
      $errors['address'] = 'Cannot be blank';
    }

    if (!$errors) {
      DB::save_rule($rule);
    }

    return array($rule, $errors);
  }

  static function delete_http_rule($id)
  {
    $rule = self::get_rule('http', $id);
    DB::delete_rule($rule);
  }

  static function get_http_rules_for_group($id)
  {
    return DB::find_rules_by_owner_id_and_owner_type('http', $id, 'Group');
  }

  static function get_rule($rule_type, $rule_id)
  {
    $rule = DB::find_rule_by_id($rule_type, $rule_id);
    if (!$rule) throw new RecordNotFound($rule_id);
    return $rule;
  }

  static function sort_http_rules(array $ids)
  {
    foreach ($ids as $index => $id) {
      DB::update_rule_position('http', $id, $index + 1);
    }
  }
}


class RecordNotFound extends Exception
{
  public function __construct($key)
  {
    parent::__construct(sprintf('Record not found: %s', (string)$key));
  }
}







