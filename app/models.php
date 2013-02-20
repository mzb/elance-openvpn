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
}

class Group extends Model
{
  public $name;
  public $description;
}
