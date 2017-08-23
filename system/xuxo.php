<?php
defined('ENV') || define('ENV', (isset($environment) ? strtoupper($environment) : 'DEV')); // DEV/TEST/PROD
switch (ENV) {
	case 'DEV':
		error_reporting(-1);
		ini_set('display_errors', 1);
	break;

	case 'TEST':
	case 'PROD':
		ini_set('display_errors', 0);
		if (version_compare(PHP_VERSION, '5.3', '>=')) {
			error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
		} else {
			error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE);
		}
	break;

	default:
		header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
		echo 'Undefined Environment Type.';
		exit(1);
}
defined('DIR_SEPARATOR') || define('DIR_SEPARATOR', '/');
defined('XUXO_VERSION') || define('XUXO_VERSION', '1.0.0');

defined('BASEPATH') || define('BASEPATH', (rtrim((isset($basePath) ? str_replace('\\',DIR_SEPARATOR,$basePath) : NULL),DIR_SEPARATOR)).DIR_SEPARATOR);
defined('SYSPATH') || define('SYSPATH', (rtrim((isset($systemPath) ? str_replace('\\',DIR_SEPARATOR,$systemPath) : NULL),DIR_SEPARATOR)).DIR_SEPARATOR);
defined('APPPATH') || define('APPPATH', (rtrim((isset($applicationPath) ? str_replace('\\',DIR_SEPARATOR,$applicationPath) : NULL),DIR_SEPARATOR)).DIR_SEPARATOR);
defined('MODPATH') || define('MODPATH', (rtrim((isset($modulesPath) ? str_replace('\\',DIR_SEPARATOR,$modulesPath) : NULL),DIR_SEPARATOR)).DIR_SEPARATOR);

defined('BASEPATH') OR exit('No direct script access allowed');

/**
* LOAD COMMON FUNCTIONS
*/
require_once(SYSPATH.'core/functions.php');

/**
* LOAD CUSTOM ERROR HANDLER
*/
set_error_handler('error_handler');
set_exception_handler('exception_handler');
register_shutdown_function('shutdown_handler');

/**
* GLOBAL VARIABLE DEFINITION
*/
global $_uri, $_load;	//, $_proc, $_bootstraps;

/**
* LOAD ESSENTIAL CORES
*/
$_uri =& loadCore('uri');
$_load =& loadCore('loader');
$_hook =& loadCore('hook');

/**
* AUTOLOADING
*/
//$_load->auto();

/**
* START PROCESS
*/
ob_start();
$default_module = config('default','module');
$modules = modules();
$moduleNameList = (!empty($modules)) ? array_keys($modules) : array();

/**
* LOAD PRE CALL HOOK IF ANY
*/
$_hook->trigger('precall');

/**
* GET URL CALL
*/
$called_module = getCall('module');
$called_controller = getCall('controller');
$called_action = getCall('action');
$called_param = getCall('param');

/**
* LOAD MODULE
*/
$set = TRUE;
if(!in_array($called_module, $moduleNameList)) {
	saveLog("ERROR", "Module '".$called_module."' does not exist in configuration, will call default module if set.");
	if($default_module === NULL) {
		throw new Exception("Module '".$called_module."' does not exist in configuration, default module is not set in configuration.");
	} else {
		$called_module = $default_module;
	}
}

/**
* LOAD POST CALL HOOK IF ANY
*/
$_hook->trigger('postcall');

/**
* LOAD MODULE
*/
$_load->module($called_module);

/**
* LOAD PRE RENDER HOOK IF ANY
*/
$_hook->trigger('prerender');

/**
* RENDER MODULE
*/
$_bootstraps[$called_module]->render($called_controller, $called_action, $called_param);

/**
* LOAD POST RENDER HOOK IF ANY
*/
$_hook->trigger('postrender');

ob_get_flush();
//debug($_bootstraps);
?>