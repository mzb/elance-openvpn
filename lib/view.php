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
  extract($data);
  require \Slim\Slim::getInstance()->config('templates.path') . '/' . $template;
}
