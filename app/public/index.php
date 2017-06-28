<?php
define('APP_PATH',          realpath(__DIR__ . '/..') . '/');
define('LIB_PATH',          APP_PATH . 'common/lib/');
define('MODELS_PATH',       APP_PATH . 'common/models/');
define('COLLECTIONS_PATH',  APP_PATH . 'common/collections/');
define('CONFIG_PATH',       APP_PATH . 'common/config/');
define('TEMP_PATH',         APP_PATH . 'temp/');

class Bootstrap extends \Phalcon\Mvc\Application
{
    protected function _registerServices()
    {
        require_once(CONFIG_PATH . 'config.php');

        # Turn on errors on development
        if (!$config->project->production) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        }

        # Check available modules
        if (empty($config->modules)) {
            die('Add required modules to config!');
        }

        $di = new \Phalcon\DI\FactoryDefault();

        # Register config
        require_once(CONFIG_PATH . 'services.php');

        # Register routes
        $di->set('router', function() use ($config) {
            $module = reset($config->modules);

            $router = new \Phalcon\Mvc\Router(false);
            $router->removeExtraSlashes(true);
            $router->setDefaultModule($module);
            $router->setDefaultController('index');
            $router->setDefaultAction('index');

            include CONFIG_PATH . 'routes.php';

            return $router;
        });

        $this->setDI($di);

        $loader = new \Phalcon\Loader();

        # Register common namespaces
        $loader->registerNamespaces(array(
            'App\Models' => MODELS_PATH,
            'App\Lib'    => LIB_PATH,
            'App\Collections' => COLLECTIONS_PATH,
        ));

        $loader->register();
    }

    private function _initModules()
    {
        $modules = [];
        foreach ($this->config->modules as $m) {
            $modules[$m] = [
                'className' => 'App\\'.ucfirst($m) . '\\Module',
                'path'      => APP_PATH . 'app/' . $m . '/Module.php',
            ];
        }

        $this->registerModules($modules);
    }

    public function __construct()
    {
        # Require composer autoload
        require_once APP_PATH . 'vendor/autoload.php';

        $this->_registerServices();
        $this->_initModules();

        echo $this->handle()->getContent();
    }
}

new Bootstrap();