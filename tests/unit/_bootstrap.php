<?php

// Here you can initialize variables that will be available to your tests
require_once(PROJECT_PATH . '/vendor/autoload.php');
require_once(PROJECT_PATH . '/test/phalcon-php/BaseTest.php');

$files = array(
    'Exception.php',
    'Loader/Exception.php',
    'Events/EventsAwareInterface.php',
    'Text.php',
    'Loader.php'
);

foreach ($files as $file) {
    require_once(PROJECT_PATH . '/src/Phalcon/' . $file);
}

$loader = new \Phalcon\Loader();
$loader->registerNamespaces(array(
    'Phalcon' => PROJECT_PATH . '/src/Phalcon/'
));
$loader->register();

use Phalcon\Config;
use Phalcon\Loader;
use Phalcon\Mvc\Url;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Router;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\Application;
use Phalcon\Di\FactoryDefault;

$di = new FactoryDefault();

/**
 * Config
 */
$di->setShared(
    'config', function () {
    $configFile = require(TESTS_PATH . '_config/global.php');
    return new Config($configFile);
}
);

$config = $di['config'];

/**
 * View
 */
$di->setShared(
    'view', function () use ($config) {
    $view = new View();
    $view->setViewsDir($config->get('application')->viewsDir);

    return $view;
}
);

// Register the Library namespace as well as the common module
// since it needs to always be available
$loader->registerNamespaces(
    [
        'Phalcon\Test\Models'                       => $config->get('application')->modelsDir,
        'Phalcon\Test\Resultsets'                   => $config->get('application')->resultsetsDir,
        'Phalcon\Test\Collections'                  => $config->get('application')->collectionsDir,
        'Phalcon\Test\Modules\Frontend\Controllers' => $config->get('application')->modulesDir . 'frontend/controllers/',
        'Phalcon\Test\Modules\Backend\Controllers'  => $config->get('application')->modulesDir . 'backend/controllers/',
        'Phalcon\Test'                              => TESTS_PATH,
        'Phalcon\Test\Module'                       => TESTS_PATH . '/_support/Module',
    ]
);

$loader->registerDirs([
    $config->get('application')->controllersDir,
    $config->get('application')->tasksDir,
    $config->get('application')->microDir,
]);

$loader->register();

$di->setShared('loader', $loader);

/**
 * The URL component is used to generate all kind of urls in the
 * application
 */
$di->setShared(
    'url', function () use ($di) {
    $config = $di['config'];
    $config = $config->get('application');

    $url = new Url();

    $url->setStaticBaseUri($config->staticUri);
    $url->setBaseUri($config->baseUri);

    return $url;
}
);

/**
 * Router
 */
$di->setShared(
    'router', function () {
    return new Router(false);
}
);

/**
 * Dispatcher
 */
$di->set('dispatcher', Dispatcher::class);

/**
 * Initialize the Database connection
 */
$di->set(
    'db', function () use ($di) {
    $config  = $di['config'];
    $config  = $config->get('database')->toArray();
    $adapter = '\Phalcon\Db\Adapter\Pdo\\' . $config['adapter'];

    unset($config['adapter']);

    /** @var \Phalcon\Db\AdapterInterface $connection */
    $connection = new $adapter($config);
    $connection->execute('SET NAMES UTF8', []);

    return $connection;
}
);

$application = new Application();
$application->setDI($di);

FactoryDefault::setDefault($di);
return $application;
