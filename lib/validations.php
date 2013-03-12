<?php

function is_valid_hostname($string)
{
  return preg_match(
    '/^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$/',
    $string
  );
}

// This also accepts format X.X.X.X/Y, where Y = 0..32
function is_valid_ip4_address($string)
{
  return preg_match(
    '/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])(\/([0-9]|1[0-9]|2[0-9]|3[0-2]))?$/',
    $string
  );
}

function is_valid_host($string)
{
  return is_valid_hostname($string) || is_valid_ip4_address($string);
}

function is_valid_port_number($n)
{
  return preg_match('/^\d+$/', $n) && intval($n) <= 65535;
}
