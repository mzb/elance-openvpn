<?php

class Model
{
  public $id;

  public function __construct($attrs = array())
  {
    foreach ($attrs as $k => $v) {
      $this->$k = $v;
    }
  }
}


class User extends Model
{
  public $username;
  public $fullname;
  public $suspended = 0;
  public $group_id;
  public $redirect_all_traffic = 0;
  public $ip;

  /** @var Group */
  public $group;

  public function is_member($group = null)
  {
    if ($group) {
      return $this->group_id == $group->id;
    }
    return $this->group_id > 0;
  }
}

class Group extends Model
{
  public $name;
  public $description;
  public $redirect_all_traffic = 0;
}


class AccessRule extends Model
{
  public $address;
  public $allow;
  public $position = 0;
  public $owner_type;
  public $owner_id;

  static function factory($type, $attrs = array())
  {
    $class = "{$type}AccessRule";
    if (!class_exists($class)) {
      throw new RuntimeException(sprintf('Unknown access rule type: %s', $class));
    }
    return new $class($attrs);
  }
}

class HTTPAccessRule extends AccessRule
{
  public $http;
  public $https;
}

class TCPAccessRule extends AccessRule
{
  public $port;
  public $tcp;
  public $udp;
}

