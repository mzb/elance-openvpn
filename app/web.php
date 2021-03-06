<?php

require '../lib/Slim/Slim.php';
\Slim\Slim::registerAutoloader();
require '../lib/Slim/Extras/Middleware/CsrfGuard.php';
require '../lib/view.php';
require 'core.php';


Core::init(require __DIR__ . '/../config/' . getenv('SLIM_MODE') . '.php');

$app = new \Slim\Slim(array(
  'templates.path' => __DIR__ . '/templates',
  'debug' => false,
  'log.enabled' => true,
));
$app->configureMode('production', function() use ($app) {
  $app->config(array('log.level' => \Slim\Log::ERROR));
});
$app->configureMode('development', function() use ($app) {
  $app->config(array('log.level' => \Slim\Log::DEBUG));
});

$app->add(new \Slim\Middleware\SessionCookie(array(
  'expires' => null,
  'httponly' => true,
  'secret' => 's1kr3t'
)));
$app->add(new \Slim\Extras\Middleware\CsrfGuard());

$app->hook('slim.before.dispatch', function() use ($app) {
  if (!$app->request()->isXhr()) {
    $app->view(new LayoutView());
    $app->view()->setLayout('layout.phtml');
  } else {
    $app->view()->setData('app', $app);
  }
});

$app->error(function(\Exception $e) use ($app) {
  $app->getLog()->error($e);
  if ($e instanceof RecordNotFound) {
    $app->notFound();
  }
  if (!$app->request()->isXhr()) {
    echo require('templates/500.html');
  }
});

$app->notFound(function() use ($app) {
  if (!$app->request()->isXhr()) {
    $app->view(new LayoutView());
    $app->view()->setLayout('layout.phtml');
    $app->render('404.phtml', array(
      'section' => null
    ));
  }
});


$section = function($section) use ($app) {
  return function() use ($section, $app) {
    $app->view()->setData('section', $section);
  };
};

$users_index = function() use ($app) {
  $app->render('users/index.phtml', array(
    'users' => Core::list_users()
  ));
};

$users_show = function($id) use ($app) {
  $user = Core::get_user($id);
  if ($user->is_member()) {
    $user->group = Core::get_group($user->group_id);
  }

  $app->render('users/show.phtml', array(
    'user' => $user,
    'http_rules' => Core::get_http_rules_for_user($id),
    'tcp_rules' => Core::get_tcp_rules_for_user($id),
    'http_group_rules' => Core::get_http_rules_for_group($user->group_id),
    'tcp_group_rules' => Core::get_tcp_rules_for_group($user->group_id),
    'groups' => Core::list_groups()
  ));
};

$users_update = function($id) use ($app) {
  Core::update_user($id, $app->request()->params('fullname'));
  $app->flash('success', 'Changes saved');
  $app->redirect($app->urlFor('users.show', array('id' => $id)));
};

$users_toggle_suspend = function($id) use ($app) {
  $user = Core::toggle_user_suspend($id);

  $app->flashNow('success', $user->suspended ? 'Access suspended' : 'Access unsuspended');
  $app->render('users/_toggle_suspend.phtml', array(
    'user' => $user
  ));
};

$users_redirect_all_traffic = function($id) use ($app) {
  $user = Core::set_redirect_all_user_traffic(
    $id,
    $app->request()->params('redirect_all_traffic')
  );

  $app->flashNow('success', 'Saved!');
  $app->render('users/_redirect_all_traffic.phtml', array(
    'user' => $user
  ));
};

$users_set_default_policy = function($id) use ($app) {
  $user = Core::set_default_user_policy(
    $id,
    $app->request()->params('default_policy')
  );

  $app->flashNow('success', 'Saved!');
  $app->render('users/_default_policy.phtml', array(
    'user' => $user
  ));
};

$users_new = function() use ($app) {
  $user = new User();

  $app->render('users/new.phtml', array(
    'user' => $user,
    'errors' => null
  ));
};

$users_create = function() use ($app) {
  list($user, $errors) = Core::create_user(
    $app->request()->params('username'), 
    $app->request()->params('fullname')
  );

  if (!$errors) {
    $app->flash('success', 'User added');
    $app->redirect($app->urlFor('users'));
  }

  $app->render('users/new.phtml', array(
    'user' => $user,
    'errors' => $errors
  ), 400);
};

$users_delete = function($id) use ($app) {
  Core::delete_user($id);
  $app->flash('success', 'User deleted');
  $app->redirect($app->urlFor('users'));
};

$users_config = function($id, $os) use ($app) {
  $config = Core::get_openvpn_config_for_user($id, $os);

  $app->response()->header('Content-Type', 'application/octet-stream');
  $app->response()->header('Content-Length', filesize($config));
  $app->response()->header('Content-Disposition', 
    sprintf('attachment; filename="%s"', basename($config))
  );
  $app->response()->header('Pragma', 'no-cache');
  readfile($config);
};

$users_keys = function($id) use ($app) {
  $keys = Core::get_openvpn_keys_for_user($id);

  $app->response()->header('Content-Type', 'application/octet-stream');
  $app->response()->header('Content-Length', filesize($keys));
  $app->response()->header('Content-Disposition', 
    sprintf('attachment; filename="%s"', basename($keys))
  );
  $app->response()->header('Pragma', 'no-cache');
  readfile($keys);
};

$users_membership = function($id) use ($app) {
  Core::update_user_membership($id, $app->request()->params('group_id'));
  $app->flash('success', 'User group changed');
  $app->redirect($app->urlFor('users.show', array('id' => $id)));
};

$app->get('/', $section('users'), $users_index);
$app->get('/users', $section('users'), $users_index)->name('users');
$app->get('/users/new', $section('users'), $users_new)->name('users.new');
$app->post('/users', $section('users'), $users_create)->name('users.create');
$app->get('/users/:id', $section('users'), $users_show)->name('users.show');
$app->post('/users/:id', $section('users'), $users_update)->name('users.update');
$app->delete('/users/:id', $section('users'), $users_delete)->name('users.delete');
$app->post('/users/:id/toggle_suspend', $section('users'), $users_toggle_suspend)->name('users.toggle_suspend');
$app->post('/users/:id/redirect_all_traffic', $section('users'), $users_redirect_all_traffic)->name('users.redirect_all_traffic');
$app->get('/users/:id/config/:os', $section('users'), $users_config)->name('users.config');
$app->get('/users/:id/keys', $section('users'), $users_keys)->name('users.keys');
$app->post('/users/:id/membership', $section('users'), $users_membership)->name('users.membership');
$app->post('/users/:id/default_policy', $section('users'), $users_set_default_policy)->name('users.default_policy');


$groups_index = function() use ($app) {
  $app->render('groups/index.phtml', array(
    'groups' => Core::list_groups()
  ));
};

$groups_show = function($id) use ($app) {
  $app->render('groups/show.phtml', array(
    'group' => Core::get_group($id),
    'members' => Core::get_group_members($id),
    'http_rules' => Core::get_http_rules_for_group($id),
    'tcp_rules' => Core::get_tcp_rules_for_group($id),
    'errors' => null
  ));
};

$groups_update = function($id) use ($app) {
  list($_, $errors) = Core::update_group(
    $id,
    $app->request()->params('name'),
    $app->request()->params('description')
  );
  if (!$errors) {
    $app->flash('success', 'Changes saved');
    $app->redirect($app->urlFor('groups.show', array('id' => $id)));
  }

  $app->render('groups/show.phtml', array(
    'group' => Core::get_group($id),
    'members' => Core::get_group_members($id),
    'http_rules' => Core::get_http_rules_for_group($id),
    'tcp_rules' => Core::get_tcp_rules_for_group($id),
    'errors' => $errors
  ), 400);
};

$groups_new = function() use ($app) {
  $group = new Group();

  $app->render('groups/new.phtml', array(
    'group' => $group,
    'errors' => null
  ));
};

$groups_create = function() use ($app) {
  list($group, $errors) = Core::create_group(
    $app->request()->params('name'),
    $app->request()->params('description')
  );

  if (!$errors) {
    $app->flash('success', 'Group added');
    $app->redirect($app->urlFor('groups'));
  }

  $app->render('groups/new.phtml', array(
    'group' => $group,
    'errors' => $errors
  ), 400);
};

$groups_delete = function($id) use ($app) {
  Core::delete_group($id);
  $app->flash('success', 'Group deleted');
  $app->redirect($app->urlFor('groups'));
};

$groups_set_redirect_all_traffic = function($id) use ($app) {
  $group = Core::set_redirect_all_group_traffic(
    $id,
    $app->request()->params('redirect_all_traffic')
  );

  $app->flashNow('success', 'Saved!');
  $app->render('groups/_redirect_all_traffic.phtml', array(
    'group' => $group
  ));
};

$groups_set_default_policy = function($id) use ($app) {
  $group = Core::set_default_group_policy(
    $id,
    $app->request()->params('default_policy')
  );

  $app->flashNow('success', 'Saved!');
  $app->render('groups/_default_policy.phtml', array(
    'group' => $group
  ));
};

$app->get('/groups', $section('groups'), $groups_index)->name('groups');
$app->get('/groups/new', $section('groups'), $groups_new)->name('groups.new');
$app->post('/groups', $section('groups'), $groups_create)->name('groups.create');
$app->get('/groups/:id', $section('groups'), $groups_show)->name('groups.show');
$app->post('/groups/:id', $section('groups'), $groups_update)->name('groups.update');
$app->delete('/groups/:id', $section('groups'), $groups_delete)->name('groups.delete');
$app->post('/groups/:id/redirect_all_traffic', $section('groups'), $groups_set_redirect_all_traffic)->name('groups.redirect_all_traffic');
$app->post('/groups/:id/default_policy', $section('groups'), $groups_set_default_policy)->name('groups.default_policy');

$rules_reload_owner = function($owner_type, $owner_id) use ($app) {
  Core::execute_reload_rule_owner($owner_type, $owner_id);
};

$app->post('/rules/reload/:owner_type/:owner_id', $rules_reload_owner)->name('rules.reload_owner');

$http_rules_save = function($id = null) use ($app) {
  $req = $app->request();
  list($rule, $errors) = Core::save_http_rule(
    $req->params('owner_type'),
    $req->params('owner_id'),
    $req->params('http'),
    $req->params('https'),
    $req->params('allow'),
    $req->params('address'),
    $id,
    $req->params('bulk') ? false : true
  );

  if ($errors) {
    $app->flashNow('error', reset($errors));
  } else {
    $app->flashNow('success', 'Saved!');
  }

  $app->render('rules/_http_form.phtml', array(
    'rule' => $rule,
    'errors' => $errors,
  ), $errors ? 400 : 200);
};

$http_rules_delete = function($id) use ($app) {
  Core::delete_http_rule($id);
};

$http_rules_sort = function() use ($app) {
  Core::sort_http_rules((array) $app->request()->params('rule'));
};

$tcp_rules_save = function($id = null) use ($app) {
  $req = $app->request();
  list($rule, $errors) = Core::save_tcp_rule(
    $req->params('owner_type'),
    $req->params('owner_id'),
    $req->params('tcp'),
    $req->params('udp'),
    $req->params('allow'),
    $req->params('address'),
    $req->params('port'),
    $id,
    $req->params('bulk') ? false : true
  );

  if ($errors) {
    $app->flashNow('error', reset($errors));
  } else {
    $app->flashNow('success', 'Saved!');
  }

  $app->render('rules/_tcp_form.phtml', array(
    'rule' => $rule,
    'errors' => $errors,
  ), $errors ? 400 : 200);
};

$tcp_rules_delete = function($id) use ($app) {
  Core::delete_tcp_rule($id);
};

$tcp_rules_sort = function() use ($app) {
  Core::sort_tcp_rules((array) $app->request()->params('rule'));
};

$app->post('/rules/http/sort', $http_rules_sort)->name('http_rules.sort');
$app->post('/rules/http/:id', $http_rules_save)->name('http_rules.update');
$app->delete('/rules/http/:id', $http_rules_delete)->name('http_rules.delete');
$app->post('/rules/http', $http_rules_save)->name('http_rules.create');
$app->post('/rules/tcp/sort', $tcp_rules_sort)->name('tcp_rules.sort');
$app->post('/rules/tcp/:id', $tcp_rules_save)->name('tcp_rules.update');
$app->delete('/rules/tcp/:id', $tcp_rules_delete)->name('tcp_rules.delete');
$app->post('/rules/tcp', $tcp_rules_save)->name('tcp_rules.create');

$password_edit = function() use ($app) {
  $app->render('password_edit.phtml', array(
    'password' => null,
    'confirmation' => null,
    'errors' => null
  ));
};

$password_update = function() use ($app) {
  $password = $app->request()->params('password');
  $confirmation = $app->request()->params('confirmation');

  $errors = Core::change_admin_password($password, $confirmation);

  if (!$errors) {
    $app->flash('success', 'Password changed');
    $app->redirect($app->urlFor('password.edit'));
  }

  $app->render('password_edit.phtml', array(
    'password' => $password,
    'confirmation' => $confirmation,
    'errors' => $errors
  ), 400);
};

$app->get('/password', $section('password.edit'), $password_edit)->name('password.edit');
$app->post('/password', $section('password.edit'), $password_update)->name('password.update');


return $app;
