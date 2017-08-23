<?php
defined('BASEPATH') OR exit('No direct script access allowed');
defined('DIR_SEPARATOR') || define('DIR_SEPARATOR', '/');
defined('XUXO_VERSION') || define('XUXO_VERSION', '1.0.0');

class Flashdata {
	function __construct() {		
		$php = phpversion();
		$version = explode(".",$php);
		if ($version[0] >= 5 && $version[1] >= 5 && $version[2] >= 0) {
			if (session_status() == PHP_SESSION_NONE) session_start();
		} else {
			if(session_id() == '') session_start();
		}
		unset($php, $version);
	}

	function set($data) {
		if(!$data) return;
		$_SESSION['FLASHDATA'][] = $data;
		return $this;
	}

	function clear($index = NULL) {
		if ($index === NULL) {
			unset($_SESSION['FLASHDATA']);
		} else {
			if(isset($_SESSION['FLASHDATA'][$index])) unset($_SESSION['FLASHDATA'][$index]);
		}
		return $this;
	}
	
	function get($index = NULL, $destroy = TRUE) {		
		if ($index === NULL) {
			$return = (isset($_SESSION['FLASHDATA'])) ? $_SESSION['FLASHDATA'] : NULL;
			$this->clear();
		} else {
			if (isset($_SESSION['FLASHDATA'][$index])) {
				$return = $_SESSION['FLASHDATA'][$index];
				if($destroy) $this->clear($index);
			} else {
				$return = NULL;
			}
		}
		return $return;
	}
	
}















?>