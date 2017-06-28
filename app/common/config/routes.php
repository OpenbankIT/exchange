<?php
	# Use for module-by-subdomain routing
	$router->add('/:params', array(
		'controller' => 'index',
		'action' => 'index'
	));

	$router->add('/:controller/:action/:params', array(
		'controller' => 1,
		'action' => 2,
		'params' => 3
	));

	$router->add('/:controller/:action', array(
		'controller' => 1,
		'action' => 2,
	));

	$router->add('/:controller', array(
		'controller' => 1,
	));
	
	# Use for module-by-slug routing
	/*$router->add('/:module/:params', array(
		'module' => 1,
		'controller' => 'index',
		'action' => 'index'
	));

	$router->add('/:module/:controller/:action/:params', array(
		'module' => 1,
		'controller' => 2,
		'action' => 3,
		'params' => 4
	));

	$router->add('/:module/:controller/:action', array(
		'module' => 1,
		'controller' => 2,
		'action' => 3,
	));

	$router->add('/:module/:controller', array(
		'module' => 1,
		'controller' => 2,
	));*/

	// $router->notFound(array(
	    // "controller" => "index",
	    // "action" => "index"
	// ));