<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Xuxo_Log_Handler {
	
	protected $_path;
	protected $_permission = 0644;
	protected $_types = array(1,2,3,4);
	protected $_date_format = 'Y-m-d H:i:s';
	protected $_ext;
	protected $_enabled = TRUE;
	protected $_levels = array('ERROR' => 1, 'DEBUG' => 2, 'INFO' => 3, 'ALL' => 4);

	public function __construct() {
		$config = config('log');

		$this->_path = (isset($config['path']) && $config['path']) ? BASEPATH.$config['path'] : APPPATH.DIR_SEPARATOR.'logs'.DIR_SEPARATOR;
		$this->_ext = (isset($config['extension']) && $config['extension'] !== '') ? ltrim($config['extension'], '.') : 'php';
		chmod(APPPATH,0777);
		
		//debug($this->_path);
		//echo file_exists($this->_path) ? "YES" : "NO";
		
		file_exists($this->_path) OR mkdir($this->_path, 0777, TRUE);

		if (!is_dir($this->_path) OR ! $this->_writable($this->_path)) $this->_enabled = FALSE;

		$this->_types = (isset($config['types']) && is_array($config['types'])) ? (array) $config['types'] : array(1,2, 3, 4);

		if (isset($config['date_format']) && $config['date_format']) $this->_date_format = $config['date_format'];
		if (isset($config['permission']) && $config['permission']) $this->_permission = $config['permission'];
	}
	
	public function write($level, $msg) {
		if ($this->_enabled === FALSE) return FALSE;
		$level = strtoupper($level);

		if(!isset($this->_levels[$level])) return FALSE;
		if(!in_array(trim($this->_levels[$level]), $this->_types)) return FALSE;
		
		$file = (trim(rtrim($this->_path,DIR_SEPARATOR).DIR_SEPARATOR.'log-'.(date('Ymd')).'.'.$this->_ext));
		$message = '';

		if (!file_exists($file)) {
			$new = TRUE;
			if ($this->_ext === 'php') $message .= "<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>\n\n";
		}
		
		if (!$fp = @fopen($file, 'a+')) return FALSE;
		flock($fp, LOCK_EX);
		
		if (strpos($this->_date_format, 'u') !== FALSE) {
			$microtime_full = microtime(TRUE);
			$microtime_short = sprintf("%06d", ($microtime_full - floor($microtime_full)) * 1000000);
			$date = new DateTime(date('Y-m-d H:i:s.'.$microtime_short, $microtime_full));
			$date = $date->format($this->_date_format);
		} else {
			$date = date($this->_date_format);
		}

		$message .= $level.': ['.$date.'] --> '.$msg."\n";

		for ($written = 0, $length = strlen($message); $written < $length; $written += $result) {
			if (($result = fwrite($fp, substr($message, $written))) === FALSE) break;
		}

		flock($fp, LOCK_UN);
		fclose($fp);

		if (isset($new) && $new === TRUE) chmod($file, $this->_permission);
		
		return is_int($result);
	}	
	
	private function _writable($file) {
		if (DIRECTORY_SEPARATOR === '/' && (check_php('5.4') OR ! ini_get('safe_mode'))) return is_writable($file);
		
		if (is_dir($file)) {
			$file = rtrim($file, '/').'/'.md5(mt_rand());
			if (($fp = @fopen($file, 'ab')) === FALSE) return FALSE;

			fclose($fp);
			@chmod($file, 0777);
			@unlink($file);
			return TRUE;
		} elseif (!is_file($file) OR ($fp = @fopen($file, 'ab')) === FALSE) {
			return FALSE;
		}

		fclose($fp);
		return TRUE;
	}
	
	public function _debug($item) {
		echo "<pre>";
		print_r($item);
		echo "</pre>";
	}
	
}
