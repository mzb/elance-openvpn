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
}

class Group extends Model
{
  public $name;
  public $description;
}


class AccessRule extends Model
{
  public $address;
  public $action;
  public $position;
  public $owner_type;
  public $owner_id;
}

class HTTPAccessRule extends AccessRule
{
  public $http = 1;
  public $https = 0;
}

class TCPAccessRule extends AccessRule
{
  public $port;
  public $tcp = 1;
  public $udp = 0;
}

