<?php

namespace App\Frontend;

class Module
{
	public function registerAutoloaders()
	{
		$loader = new \Phalcon\Loader();

		$loader->registerNamespaces(array(
			__NAMESPACE__ . '\Controllers' => __DIR__ . '/controllers/',
		));

		$loader->register();
	}

	public function registerServices($di)
	{
		//Registering a dispatcher
		$di->set('dispatcher', function () {
			$dispatcher = new \Phalcon\Mvc\Dispatcher();
			$dispatcher->setDefaultNamespace(__NAMESPACE__ . '\Controllers\\');
			return $dispatcher;
		});

		//Registering the view component
		$di->set('view', function () {
			$view = new \Phalcon\Mvc\View();
			$view->setViewsDir(__DIR__ .'/views/');
			return $view;
		});
	}
}