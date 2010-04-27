<?php

require_once __DIR__ . '/vendor/symfony/src/Symfony/Foundation/UniversalClassLoader.php';

use Symfony\Foundation\UniversalClassLoader;

$loader = new UniversalClassLoader();
$loader->registerNamespaces(array(
  'OpenSky'		=> __DIR__ . '/lib',
  'Symfony'		=> __DIR__ . '/vendor/symfony',
));
$loader->registerPrefixes(array(
  'Zend_'  => __DIR__ . '/vendor/zend/library',
));
$loader->register();
