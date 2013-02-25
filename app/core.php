<?php

require 'models.php';
require 'db.php';
require '../lib/validations.php';


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

    self::execute('create_user', array($user->username));
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
    if ($user->suspended) {
      self::execute('disable_user', array($user->username));
    } else {
      self::execute('enable_user', array($user->username));
    }

    DB::update_user($user);

    return $user;
  }

  static function delete_user($id)
  {
    $user = self::get_user($id);
    self::execute('del_user', array($user->username));
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

  static function save_http_rule($owner_type, $owner_id, $http, $https, $allow, $address, 
                                 $id = null)
  {
    if ($id) {
      $rule = self::get_rule('http', $id);
      $position = $rule->position;
    } else {
      $position = DB::get_last_rule_position('http') + 1;
    }

    $rule = AccessRule::factory('http', array(
      'id' => $id,
      'owner_type' => $owner_type, 
      'owner_id' => $owner_id, 
      'http' => $http, 
      'https' => $https, 
      'allow' => $allow, 
      'address' => $address,
      'position' => $position
    ));

    $errors = array();
    if (!is_valid_host(trim($rule->address))) {
      $errors['address'] = 'Invalid domain or IP address';
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

  static function get_http_rules_for_user($id)
  {
    return DB::find_rules_by_owner_id_and_owner_type('http', $id, 'User');
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

  static function save_tcp_rule($owner_type, $owner_id, $tcp, $udp, $allow, $address, 
                                $port, $id = null)
  {
    if ($id) {
      $rule = self::get_rule('tcp', $id);
      $position = $rule->position;
    } else {
      $position = DB::get_last_rule_position('tcp') + 1;
    }

    $rule = AccessRule::factory('tcp', array(
      'id' => $id,
      'owner_type' => $owner_type, 
      'owner_id' => $owner_id, 
      'tcp' => $tcp, 
      'udp' => $udp, 
      'allow' => $allow, 
      'address' => $address,
      'port' => $port,
      'position' => $position
    ));

    $errors = array();
    if (!is_valid_host(trim($rule->address))) {
      $errors['address'] = 'Invalid domain or IP address';
    }
    if ($rule->port && !is_valid_port_number($rule->port)) {
      $errors['port'] = 'Invalid port number (accepted range: [0..65535])';
    }

    if (!$errors) {
      DB::save_rule($rule);
    }

    return array($rule, $errors);
  }

  static function delete_tcp_rule($id)
  {
    $rule = self::get_rule('tcp', $id);
    DB::delete_rule($rule);
  }

  static function get_tcp_rules_for_group($id)
  {
    return DB::find_rules_by_owner_id_and_owner_type('tcp', $id, 'Group');
  }

  static function get_tcp_rules_for_user($id)
  {
    return DB::find_rules_by_owner_id_and_owner_type('tcp', $id, 'User');
  }

  static function sort_tcp_rules(array $ids)
  {
    foreach ($ids as $index => $id) {
      DB::update_rule_position('tcp', $id, $index + 1);
    }
  }

  static function change_admin_password($password, $confirmation)
  {
    $errors = array();
    $password = trim($password);
    $confirmation = trim($confirmation);

    if (!$password) {
      $errors['password'] = 'Password cannot be blank';
    }

    if ($password !== $confirmation) {
      $errors['confirmation'] = 'Confirmation does not match password';
    }

    if (!$errors) {
      self::execute('change_admin_pass', array($password));
    }

    return $errors;
  }

  static function get_openvpn_config_for_user($user_id, $os)
  {
    $user = self::get_user($user_id);
    return self::execute('dl_user_conf', array($user->username, $os));
  }

  private static function execute($script, $args = array())
  {
    $ouput = array();
    $error = 0;

    $cmd = array("./../scripts/{$script}");
    foreach ($args as $arg) {
      $cmd[] = escapeshellarg($arg);
    }
    $cmd = join(' ', $cmd);

    \Slim\Slim::getInstance()->getLog()->debug("Core::execute: $cmd");
    exec($cmd, $output, $error);
    \Slim\Slim::getInstance()->getLog()->debug('Core::execute => ' . json_encode($output));
    \Slim\Slim::getInstance()->getLog()->debug("Core::execute error? $error");

    if ($error) {
      throw new RuntimeException(sprintf('Script failed: %s (%s)', $script, $error));
    }

    return $output ? $output[0] : null;
  }
}


class RecordNotFound extends Exception
{
  public function __construct($key)
  {
    parent::__construct(sprintf('Record not found: %s', (string)$key));
  }
}
