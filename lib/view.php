<?php

class LayoutView extends \Slim\View
{
  protected $layout;

  public function setLayout($layout)
  {
    $this->layout = $this->getTemplatesDirectory() . '/' . ltrim($layout, '/');
    if (!file_exists($this->layout)) {
      throw new RuntimeException(sprintf('Layout not found: "%s"', $this->layout));
    }
  }

  public function render($template)
  {
    $this->setTemplate($template);

    $this->data['app'] = \Slim\Slim::getInstance();
    $this->data['params'] = $this->data['app']->request()->params();
    extract($this->data);

    ob_start();
    require $this->templatePath;
    $__content__ = ob_get_clean();

    ob_start();
    require $this->layout;
    return ob_get_clean();
  }
}

function partial($template, $data = array())
{
  $data['app'] = \Slim\Slim::getInstance();
  $data['params'] = $data['app']->request()->params();
  if (!isset($data['flash'])) $data['flash'] = array();
  extract($data);
  require \Slim\Slim::getInstance()->config('templates.path') . '/' . $template;
}

function escape_html($text, $flags = ENT_COMPAT, $double_encode = true)
{
  return htmlspecialchars($text, $flags, 'UTF-8', $double_encode);
}
function h($text, $flags = ENT_COMPAT, $double_encode = true)
{
  return escape_html($text, $flags, $double_encode);
}
