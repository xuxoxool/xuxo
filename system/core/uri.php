<?php

defined('BASEPATH') OR exit('No direct script access allowed');
defined('DIR_SEPARATOR') || define('DIR_SEPARATOR', '/');
defined('XUXO_VERSION') || define('XUXO_VERSION', '1.0.0');
class Xuxo_URI {
	protected $data = NULL;
	
	public function __construct($get = TRUE) {
		if ($get) $this->parseURI();
			
		return $this;
	}
	
	/**
	* PARSE
	*/
	public function parseURI() {	
		if ( ! isset($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME'])) return NULL;
		
		$req = parse_url('http://example.com'.$_SERVER['REQUEST_URI']);
		$query = isset($req['query']) ? $req['query'] : '';
		$path = isset($req['path']) ? $req['path'] : '';
		unset($req);
		
		$data = NULL;
		$data['path'] = $path;
		$data['front_pointer'] = trim(str_replace(DIRECTORY_SEPARATOR,DIR_SEPARATOR,(dirname($_SERVER['SCRIPT_FILENAME']))),DIR_SEPARATOR);
		$data['front_script_name'] = trim(str_replace(DIRECTORY_SEPARATOR,DIR_SEPARATOR,basename($_SERVER['SCRIPT_NAME'])),DIR_SEPARATOR);
		$data['front_script_path'] = trim(str_replace(DIRECTORY_SEPARATOR,DIR_SEPARATOR,dirname($_SERVER['SCRIPT_NAME'])),DIR_SEPARATOR);
		$data['protocol'] = (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=="off") ? 'https' : 'http';
		$data['host'] = (isset($_SERVER['SERVER_NAME'])) ? trim($_SERVER['SERVER_NAME'],DIR_SEPARATOR) : NULL;		
		$data['base'] = $data['protocol']."://".$data['host'].DIR_SEPARATOR.$data['front_script_path'].DIR_SEPARATOR;
		$data['call_string'] = trim(str_replace($data, '', trim($path,DIR_SEPARATOR)),DIR_SEPARATOR);
		$data['query_string'] = $query;
		
		if($data) {
			foreach($data as $k=>&$d) {
				$d = ($k == 'base') ? $d : self::_removeDuplicateSeparator($d);
			}
		}
		$this->data = $data;
		
		$this->parseElements();
		$this->parseQueries();
		
		return $this->data;
	}
	
	public function parseElements() {	
		$elements = array();
		if($string = (isset($this->data['call_string']) && $this->data['call_string']) ? $this->data['call_string'] : NULL) {		
			if($list = explode(DIR_SEPARATOR,$string)) {
				foreach($list as $key=>$value) {
					if($key == 0) $elements['module'] = $value;
					if($key == 1) $elements['controller'] = $value;
					if($key == 2) $elements['action'] = $value;
					if($key > 2) $elements['param'][] = $value;
				}
			}
		}
		return ($this->data['elements'] = $elements);
	}
	
	public function parseQueries() {
		$queries = array();
		if($string = (isset($this->data['query_string']) && $this->data['query_string']) ? $this->data['query_string'] : NULL) {			
			if($segments = explode("&",$string)) {
				foreach($segments as $segment) {
					$elem = explode("=",$segment);
					$queries[$elem[0]] = $elem[1];
				}
			}
		}
		return ($this->data['queries'] = $queries);
	}
	
	/**
	* SET
	*/
	public function setElement($type, $value) {
		if(!$type) return NULL;
		if(!in_array(strtolower($type), array('module','controller','action'))) return NULL;
		return ($this->data['elements'][strtolower($type)] = $value);
	}
	
	public function setParam($key, $value) {
		if(!$key) return NULL;
		return ($this->data['elements']['param'][$key] = $value);
	}
	
	/**
	* GET
	*/
	public static function getBaseUrl() {
		$front = trim(str_replace(DIRECTORY_SEPARATOR,DIR_SEPARATOR,dirname($_SERVER['SCRIPT_NAME'])),DIR_SEPARATOR);
		$protocol = (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=="off") ? 'https' : 'http';
		$host = (isset($_SERVER['SERVER_NAME'])) ? trim($_SERVER['SERVER_NAME'],DIR_SEPARATOR) : NULL;		
		return $protocol."://".(self::_removeDuplicateSeparator($host.DIR_SEPARATOR.$front.DIR_SEPARATOR));
	}
	
	public function getCall($type = NULL) {
		return $this->getElement($type);
	}
	
	public function getElement($type = NULL) {
		if($type && !in_array(strtolower($type), array('module','controller','action','param'))) return NULL;
		if($type) {
			return ($this->data['elements'] && isset($this->data['elements'][strtolower($type)])) ? $this->data['elements'][strtolower($type)] : NULL;
		} else {
			return $this->data['elements'];
		}
	}
	
	public function getQuery($type = NULL) {
		if($type) {
			return ($this->data['queries'] && isset($this->data['queries'][strtolower($type)])) ? $this->data['queries'][strtolower($type)] : NULL;
		} else {
			return $this->data['queries'];
		}
	}
	
	public function getData() { return $this->data; }
	
	public function getProtocol() { return (isset($this->data['protocol']) && $this->data['protocol']) ? $this->data['protocol'] : NULL; }
	
	public function getHost() { return (isset($this->data['host']) && $this->data['host']) ? $this->data['host'] : NULL; }
	
	public function getBase() { return (isset($this->data['base']) && $this->data['base']) ? $this->data['base'] : NULL; }
	
	public function getCallString() { return (isset($this->data['call_string']) && $this->data['call_string']) ? $this->data['call_string'] : NULL; }
	
	/**
	* HELPER
	*/
	protected static function _removeDuplicateSeparator($string) {
		$return = array();
		$tok = strtok($string, DIR_SEPARATOR);
		while ($tok !== FALSE) {
			if (( ! empty($tok) OR $tok === '0') && $tok !== '..') $return[] = $tok;
			$tok = strtok(DIR_SEPARATOR);
		}
		return implode(DIR_SEPARATOR, $return);
	}
	
}