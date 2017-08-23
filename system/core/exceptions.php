<?php

defined('BASEPATH') OR exit('No direct script access allowed');
defined('DIR_SEPARATOR') || define('DIR_SEPARATOR', '/');
defined('XUXO_VERSION') || define('XUXO_VERSION', '1.0.0');

class Xuxo_Exceptions {
	
	public $ob_level;
	
	public $errlvl = array(
		E_ERROR			=>	'Error',
		E_WARNING		=>	'Warning',
		E_PARSE			=>	'Parsing Error',
		E_NOTICE		=>	'Notice',
		E_CORE_ERROR		=>	'Core Error',
		E_CORE_WARNING		=>	'Core Warning',
		E_COMPILE_ERROR		=>	'Compile Error',
		E_COMPILE_WARNING	=>	'Compile Warning',
		E_USER_ERROR		=>	'User Error',
		E_USER_WARNING		=>	'User Warning',
		E_USER_NOTICE		=>	'User Notice',
		E_STRICT		=>	'Runtime Notice'
	);
	
	public function __construct() {
		$this->ob_level = ob_get_level();
	}
	
	public function saveLog($severity, $message, $file, $line) {
		$severity = isset($this->levels[$severity]) ? $this->levels[$severity] : $severity;
		saveLog('error', 'Severity: '.$severity.' --> '.$message.' '.$file.' '.$line);
	}
	
	public function show($header, $message, $severity = 1, $file = NULL, $line = NULL, $type = 1, $status = 500) {
		$config = config('errors');
		
		$path	= BASEPATH;
		$path .= ($config && isset($config['path']) && $config['path']) ? trim($config['path'],'/') : APPPATH.DIR_SEPARATOR.'errors';
		$path .= DIR_SEPARATOR.(($this->_is_command_line()) ? "cli" : "web");
		
		switch($type) {
			case 1:
				$path .= DIR_SEPARATOR."error.php";
				break;
			case 2:
				$path .= DIR_SEPARATOR."404.php";
				break;
			default:
				$this->setHeader(503);
				echo 'Undefined Error Type';
				exit(3);
				break;
		}
		
		if ($this->_is_command_line()) {
			$message = "\t".(is_array($message) ? implode("\n\t", $message) : $message);
		} else {
			$this->setHeader($status);
			$message = '<p>'.(is_array($message) ? implode('</p><p>', $message) : $message).'</p>';
		}

		if (ob_get_level() > $this->ob_level + 1) ob_end_flush();
		
		if (ob_get_contents()) ob_end_clean();
		ob_start();
		include($path);
		$buffer = NULL;
		if ($buffer = ob_get_contents()) ob_end_clean();
		return $buffer;
	}

	public function error404($page = '', $log_error = TRUE) {
		$header = ($this->_is_command_line()) ? 'Not Found' : '404 Page Not Found';
		$message = ($this->_is_command_line()) ? 'The request was not found.' : 'The page you requested was not found.';
		
		if ($log_error) saveLog('error', $header.': '.$page);

		echo $this->show($header, $message, 1, $page, NULL, 2, 404);
		exit(4);
	}
	
	public function exception($exception) {
		$header = 'An Error has Occured.';
		$message = "[".(date('Y-m-d H:i:s'))."] ".$exception->getMessage()." in ".$exception->getFile()." line ".$exception->getLine();
		
		saveLog('error', $header.': '.$message);

		echo $this->show($header, $exception->getMessage(), 1, $exception->getFile(), $exception->getLine(), 1, 500);
		exit(4);
	}

	public function phpError($severity, $message, $file, $line) {
		$severity = isset($this->levels[$severity]) ? $this->levels[$severity] : $severity;
		
		$header = 'An Error has Occured.';
		$msg = "[".(date('Y-m-d H:i:s'))."] ";
		$msg .= "Severity : ".$severity." --> ".$message." in ".$file." line ".$line;
		
		saveLog('error', $header.': '.$msg);
		
		echo $this->show($header, $message, $severity, $file, $line, 1, 500);
		exit(4);
	}
	
	public function setHeader($code = 200, $text = NULL) {
		if (PHP_SAPI === 'cli' || defined('STDIN')) return;

		if (empty($code) OR ! is_numeric($code)) $this->show('ERROR', 'Status codes must be numeric', 1, NULL, NULL, 1, 500);

		if (!$text) {
			is_int($code) || $code = (int) $code;
			$stat = array(
				100	=> 'Continue',
				101	=> 'Switching Protocols',

				200	=> 'OK',
				201	=> 'Created',
				202	=> 'Accepted',
				203	=> 'Non-Authoritative Information',
				204	=> 'No Content',
				205	=> 'Reset Content',
				206	=> 'Partial Content',

				300	=> 'Multiple Choices',
				301	=> 'Moved Permanently',
				302	=> 'Found',
				303	=> 'See Other',
				304	=> 'Not Modified',
				305	=> 'Use Proxy',
				307	=> 'Temporary Redirect',

				400	=> 'Bad Request',
				401	=> 'Unauthorized',
				402	=> 'Payment Required',
				403	=> 'Forbidden',
				404	=> 'Not Found',
				405	=> 'Method Not Allowed',
				406	=> 'Not Acceptable',
				407	=> 'Proxy Authentication Required',
				408	=> 'Request Timeout',
				409	=> 'Conflict',
				410	=> 'Gone',
				411	=> 'Length Required',
				412	=> 'Precondition Failed',
				413	=> 'Request Entity Too Large',
				414	=> 'Request-URI Too Long',
				415	=> 'Unsupported Media Type',
				416	=> 'Requested Range Not Satisfiable',
				417	=> 'Expectation Failed',
				422	=> 'Unprocessable Entity',

				500	=> 'Internal Server Error',
				501	=> 'Not Implemented',
				502	=> 'Bad Gateway',
				503	=> 'Service Unavailable',
				504	=> 'Gateway Timeout',
				505	=> 'HTTP Version Not Supported'
			);

			if (isset($stat[$code])) {
				$text = $stat[$code];
			} else {
				$code = 500;
				$text = 'Error : Status type invalid.';
			}
		}

		if (strpos(PHP_SAPI, 'cgi') === 0) {
			header('Status: '.$code.' '.$text, TRUE);
		} else {
			$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
			if(ob_get_contents()) ob_end_clean();			
			
			if (!headers_sent()) {
				header($protocol.' '.$code.' '.$text, TRUE, $code);
			} else {
				debug($protocol, $code, $text);
			}
		}
	}
	
	private function _is_command_line() { return (PHP_SAPI === 'cli' || defined('STDIN')); }

	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}
?>