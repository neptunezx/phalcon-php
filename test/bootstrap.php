<?php

/**
 * Bootstrapping
 *
 * @author Wenzel Pünter <wenzel@phelix.me>
 */
require_once(dirname(__DIR__) . '/vendor/autoload.php');
require_once(__DIR__ . '/phalcon-php/BaseTest.php');

$files = array(
    'Exception.php',
    'Loader/Exception.php',
    'Events/EventsAwareInterface.php',
    'Text.php',
    'Loader.php'
);

foreach ($files as $file) {
    require_once(__DIR__ . '/../src/Phalcon/' . $file);
}

$loader = new \Phalcon\Loader();
$loader->registerNamespaces(array(
    'Phalcon' => __DIR__ . '/../src/Phalcon/'
));
$loader->register();
