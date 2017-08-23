<?php
date_default_timezone_set('Asia/Kuala_Lumpur');

/**
* ENVIRONMENT
*/
$environment = 'DEV';

/**
* BASE PATH
*/
$basePath = dirname(__FILE__);

/**
* SYSTEM PATH
*/
$systemPath = $basePath.'/system';

/**
* APPLICATION PATH
*/
$applicationPath = $basePath.'/application';

/**
* MODULES PATH
*/
$modulesPath = $basePath.'/application/modules';

/**
* CALL XUXO
*/
require_once($systemPath.'/xuxo.php');












?>