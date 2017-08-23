<?php

/**
* LOGGING CONFIGURATION
*/
$config['log']['write'] = TRUE;
$config['log']['path'] = 'application/logs';
$config['log']['ext'] = 'php';
$config['log']['types'] = array(1,2,3,4);

/**
* ERROR VIEW CONFIGURATION
*/
$config['errors']['path'] = 'application/errors';

/**
* DEFAULT CONFIGURATION
*/
$config['default']['module'] = 'home';