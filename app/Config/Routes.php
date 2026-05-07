<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->setAutoRoute(false);

$routes->get('/', 'MediaController::index');
$routes->get('catalogue', 'MediaController::catalogue');
$routes->get('catalogue/media/(:segment)/(:any)', 'MediaController::catalogueMedia/$1/$2');
$routes->get('photo', 'MediaController::photo');
$routes->get('video', 'MediaController::video');
$routes->get('logo', 'MediaController::logo');
$routes->get('(:any)', 'MediaController::notFound');
