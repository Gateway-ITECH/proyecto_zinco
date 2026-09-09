<?php
use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

$autoloader = require_once 'autoload.php';
$request = Request::createFromGlobals();
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->boot();

$config = \Drupal::config('zinco_front.dashboard.settings');
echo "show_tooltips: " . var_export($config->get('show_tooltips'), TRUE) . "\n";
