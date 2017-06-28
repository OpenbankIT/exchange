<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

declare(ticks=1);

define('APP_PATH',      realpath(__DIR__ . '/../..') . '/');
define('LIB_PATH',      APP_PATH . 'common/lib/');
define('MODELS_PATH',   APP_PATH . 'common/models/');
define('COLLECTIONS_PATH',   APP_PATH . 'common/collections/');
define('CONFIG_PATH',   APP_PATH . 'common/config/');
define('TEMP_PATH',     APP_PATH . 'temp/');

class CliApplication
{
    protected function _registerServices()
    {
        # Register config
        require_once(CONFIG_PATH . 'config.php');

        $loader = new \Phalcon\Loader();

        # Register tasks
        $loader->registerDirs(array(
            APP_PATH . 'app/cli/tasks/',
        ));

        # Register common namespaces
        $loader->registerNamespaces(array(
            'App\Models' => MODELS_PATH,
            'App\Lib' => LIB_PATH,
            'App\Collections' => COLLECTIONS_PATH
        ));

        $loader->register();


        $di = new \Phalcon\DI\FactoryDefault\CLI();

        require_once(CONFIG_PATH . 'services.php');

        return $di;
    }

    public function __construct()
    {
        global $argv;

        # Require composer autoload
        // require_once APP_PATH . 'vendor/autoload.php';

        $console = new \Phalcon\CLI\Console();
        require_once APP_PATH . 'vendor/autoload.php';
        $di = $this->_registerServices();

        $arguments = array();
        $params    = array();

        foreach($argv as $k => $arg) {
            if($k == 1) {
                $arguments['task'] = $arg;
            } elseif($k == 2) {
                $arguments['action'] = $arg;
            } elseif($k >= 3) {
                $params[] = $arg;
            }
        }

        $verbose = false;
        if(count($params) > 0) {
            if (in_array('-v', $params))
                $di->setShared('verbose', function(){
                    return true;
                });

            $arguments['params'] = $params;
        }

        $console->setDI($di);

        define('CURRENT_TASK', (isset($argv[1]) ? $argv[1] : null));
        define('CURRENT_ACTION', (isset($argv[2]) ? $argv[2] : null));

        try {
            $console->handle($arguments);
        } catch (\Phalcon\Exception $e) {
            echo $e->getMessage();
            exit(255);
        }
    }
}

$application = new CliApplication();