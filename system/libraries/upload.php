<?php

defined('BASEPATH') OR exit('No direct script access allowed');
defined('DIR_SEPARATOR') || define('DIR_SEPARATOR', '/');
defined('XUXO_VERSION') || define('XUXO_VERSION', '1.0.0');

class Upload {
	public $config = NULL;
	public $path = NULL;
	public $key = NULL;	
	public $data = NULL;
	public $formats = array();
	public $max_size = "5000000";	
	public $ori_name = "";
	public $new_name = "";
	public $type = "";
	public $size = 0;
	public $tmp_name = "";
	public $fileerror = 0;
	public $extension = "";
	public $error = "";
	public $_helper;
	
	public function __construct($config = array()) {		
		return $this->init($config);
	}
	
	public function __destruct() { unset($this); }
	
	public function init($config) {
		if(is_array($config) && !empty($config)) {
			$this->config = $config;
			
			// SET PATH
			if(isset($config['path']) && $config['path']) {
				$this->setPath($config['path']);
			}
			
			// SET KEY
			if(isset($config['key']) && $config['key']) {
				$this->setKey($config['key']);
			} else {
				$this->setKey(NULL);
			}
			
			// SET FORMAT
			if(isset($config['formats']) && $config['formats']) {
				$this->setFormat($config['formats']);
			} else {
				$this->setFormat(NULL);
			}
			
			// SET MAX SIZE
			if(isset($config['max_size']) && $config['max_size']) {
				$this->setMaxSize($config['max_size']);
			} else {
				$this->setMaxSize(NULL);
			}
			
			// SET NEW NAME
			if(isset($config['new_name']) && $config['new_name']) $this->new_name = $config['new_name'];
			
			// SET OVERWRITE
			if(isset($config['overwrite']) && $config['overwrite']) $this->overwrite = $config['overwrite'];
		} else {
			throw new Exception('Invalid configuration');
		}
		$this->fetchData();
		return $this;
	}
	
	public function setPath($path) {
		$this->path = ($path && !empty($path)) ? $path : $this->path;
		$this->path = str_replace('\\','/',$this->path);
		$this->path = $this->normalizePath($this->path);
		$this->createDir($this->path);
		$this->setPermission($this->path);
	}
	
	public function unsetPath() { $this->path = NULL; }
	
	public function appendPath($string) { $this->path .= $string; $this->path = str_replace('\\','/',$this->path);	}
	
	public function setKey($key = NULL) {
		if($key === NULL) {
			$key = (isset($_FILES) && !empty($_FILES)) ? key($_FILES) : NULL;
		}
		$this->key = ($key) ? $key : NULL;
	}
	
	public function unsetKey() { $this->_key = NULL; }
	
	public function setFormat($format = "") {
		if(is_array($format)) {
			$this->formats = (!empty($format)) ? $format : array();
		} else {
			if(trim($format)) {
				if(strpos($format,'|') !== FALSE) {
					$f = explode('|',$format);
					return $this->setFormat($f);
				} else {
					$this->formats = array(strtolower($format), strtoupper($format));
				}
			} else {
				$this->formats = array();
			}
		}
	}
	
	public function addFormat($format) {
		if($format && !empty($format) && !is_array($format)) {
			array_push($this->formats, $format);
		}
	}
	
	public function removeFormat($format) {
		if($this->formats && !empty($this->formats)) {
			foreach($this->formats as $key=>&$item) {
				if(strtoupper($item) == strtoupper($format)) {
					unset($this->formats[$key]);
				}
			}
		}
	}
	
	public function emptyFormat() { $this->formats = array(); }
	
	public function setMaxSize($size = "5000000") {
		if(!empty($size)) {
			if(strpos($size,".") !== FALSE) throw new Exception("SIZE SHOULD BE ROUND NUMBER");
			
			if(strpos($size,"0") !== FALSE) {
				$this->max_size = $size;
			} else {
				throw new Exception("INVALID NUMBER FORMAT");
			}			
		} else {
			$this->max_size = "5000000";
		}
	}
	
	public function setData($data = NULL) { $this->data = ($data && is_array($data) && !empty($data)) ? reset($data) : NULL; }
	
	public function fetchData() {
		if (isset($_FILES) && !empty($_FILES)) {
			$this->data = ($this->key) ? $_FILES[$this->key] : reset($_FILES);
		} else {
			$this->data =  NULL;
		}
	}
	
	public function emptyData() { $this->data = NULL; }
		
	public function setError($error) { $this->error = $error; }

	public function deleteFile($name, $ext=TRUE) {	
		if(!empty($this->path)) {
			chmod($this->path,0777);
			if(!$ext) {
				// DELETE FILE WITH SAME NAME REGARDLESS OF EXTENSION
				if ($uHandle = opendir($this->path)) {
					while (false !== ($uEntry = readdir($uHandle))) {							
						if(trim($uEntry) != '' && ($uEntry != '.' && $uEntry != '..') && strpos($uEntry,'.htaccess')==0) {
							$uExt = explode(".", $uEntry);
							$uName = trim($uExt[0]);
							if($uName == $name) {
								if(file_exists($this->path."/".$uEntry)) {
									chmod($this->path."/".$uEntry,0777);
									unlink($this->path."/".$uEntry);
								}
							}
						}
					}
					closedir($uHandle);
				}
			} else {
				// DELETE FILE WITH SAME NAME AND EXTENSION
				if(file_exists($this->path."/".$name)) {
					chmod($this->path."/".$name,0777);
					unlink($this->path."/".$name);
				}
			}
			return (file_exists($this->path."/".$name)) ? FALSE : TRUE;
		}
		return FALSE;
	}
	
	public function doUpload($newname = "", $deletefilesamename = FALSE, $deletefilesamenameext = TRUE) {
		$result = FALSE;
		if($this->data && !empty($this->data)) {
			// FILE PARAMETERS
			$this->ori_name = $this->data['name'];
			$this->new_name = (!$this->new_name) ? (($newname && trim($newname) != "" && !empty($newname)) ? $newname : $this->data['name']) : $this->new_name;
			$this->type = $this->data['type'];
			$this->size = $this->data['size'];
			$this->tmp_name = $this->data['tmp_name'];
			$this->fileerror = $this->data['error'];
			$nametopop = explode(".", $this->ori_name);
			$this->extension = $nametopop[count($nametopop)-1];
			$this->new_name = str_replace('.'.$this->extension,'',$this->new_name);
			unset($nametopop);
			// START
			if ($this->size <= $this->max_size) {
				if (in_array($this->extension, $this->formats)) {
					if (!($this->fileerror > 0)) {
						// CREATE UPLOAD PATH IF NOT EXISTED
						if(!file_exists($this->path)) $this->createDir($this->path);
						if($deletefilesamename || $this->overwrite) { $this->deleteFile($this->ori_name, ($deletefilesamenameext || !$this->overwrite)); }
						// UPLOAD FILE
						if(is_uploaded_file($this->tmp_name)) {
							$result = move_uploaded_file($this->tmp_name, $this->path."/".$this->new_name.".".$this->extension);
							if(!$result) {
								$this->error = "MOVE UPLOAD FAIL";
							} else {
								if (file_exists($this->path."/".$this->new_name.".".$this->extension)) {
									$this->setPermission($this->path."/".$this->new_name.".".$this->extension,0777);
									$result = TRUE;
								} else {
									$this->error = "UPLOADED FILE DOES NOT EXIST";
								}
							}
						} else {
							$this->error = "INVALID HTTP FILE REFERENCE";
						}
					} else {
						$this->error = "FILE ERROR";
					}
				} else {
					$this->error = "EXTENSION ".$this->extension." IS NOT ALLOWED";
				}
			} else {
				$this->error = "EXCEED SIZE";
			}
		} else {
			$this->error = "DATA IS EMPTY";
		}
		return $result;
	}	
	
	public function fileUploaded() { return (file_exists($this->path."/".$this->new_name.".".$this->extension)); }
		
	public function createDir($path) {
		if(!empty($path)) {
			// REPLACE \ WITH / FOR LINUX/UNIX COMPATIBILITY
			$path = str_replace('\\','/',$path);
			// CHECK PATH HAS /
			if(strpos($path, '/') !== FALSE) {
				$to = str_replace(str_replace('\\','/',BASEPATH),'',$path);
				$paths = explode('/', $to);
				$imm_par = str_replace('\\','/',BASEPATH);
				foreach($paths as $key=>&$value) {
					$imm_par	.= $value.DIR_SEPARATOR;
					
					if(!file_exists($imm_par)) @mkdir($imm_par,0777);
					$this->setPermission($imm_par);
				}
			}
						
			if(!file_exists($path)) @mkdir($path,0777);
			$this->setPermission($path);
		}
	}
	
	public function setPermission($path, $r = 7, $w = 7, $x = 7) {		
		$uname = strtolower(php_uname());
		if (strpos($uname, "linux") !== false) {
			$mode = $r.$w.$x;
			$output = shell_exec("ps -ef | grep apache");
			$output = shell_exec("sudo chown www-data:www-data ".$path);
			$output = shell_exec("sudo chmod ".$mode." ".$path);
		} else {
			$mode = "0".$r.$w.$x;
			chmod($path,$mode);
		}
	}
	
	
	//////////////////////////////////////////////////////////
	// GET FUNCTIONS
	public function getPath() { return $this->path;	}
	public function getKey() { return $this->_key;	}
	public function getMaxSize() { return $this->max_size;	}
	public function getFormat() { return $this->formats;	}
	public function getData() { return $this->data;	}
	public function getError() { return $this->error;	}
	public function getUploadedFile($full = FALSE) { return ($this->fileUploaded()) ? (($full) ? $this->path."/" : "").$this->new_name.".".$this->extension : NULL; }
	public function getExtension() { return $this->extension;	}
	
	public function getUploadedData() {
		$return = NULL;
		
		$return['directory'] = $this->path;
		$return['ori_name'] = $this->ori_name;
		$return['new_name'] = $this->new_name;
		$return['extension'] = $this->extension;
		$return['full_path'] = $return['directory'].$return['new_name'].".".$return['extension'];
		$return['file_type'] = $this->type;
		$return['file_size'] = $this->size;
		
		$pathinfo = pathinfo($return['full_path']);
		$return['file_name'] = $pathinfo['filename'];
		$return['base_name'] = $pathinfo['basename'];

		$size = getimagesize($return['full_path']);
		$return['height'] = $size[0];
		$return['width'] = $size[1];
		
		$stat = stat($return['full_path']);
		$return['size'] = number_format(($stat['size']/1000000),2)." Mb";			
		$return['date'] = date ("d/m/Y H:i:s", $stat['mtime']);
		
		unset($pathinfo, $size, $stat);		
		
		return $return;
	}
	
	public function resizeImage($image = NULL, $param = array('width'=>1, 'height'=>1, 'maxWidth'=>1, 'maxHeight'=>1, 'scaleX'=>1, 'scaleY'=>1)) {
		if(!$image) return NULL;
		if(!$param) {
			return NULL;
		} elseif(!is_array($param)) {
			return NULL;
		} else {
			$keys = array_keys($param);
			$allowed = array('width', 'height', 'maxWidth', 'maxHeight', 'scaleX', 'scaleY');
			$c = 0;
			foreach($keys as $key) {
				if(in_array($key, $allowed)) $c++;
			}
			if($c == 0) return NULL;
			$width = (isset($param['width'])) ? $param['width'] : 0;
			$height = (isset($param['height'])) ? $param['height'] : 0;
			$maxWidth = (isset($param['maxWidth'])) ? $param['maxWidth'] : 0;
			$maxHeight = (isset($param['maxHeight'])) ? $param['maxHeight'] : 0;
			$scaleX = (isset($param['scaleX'])) ? $param['scaleX'] : 0;
			$scaleY = (isset($param['scaleY'])) ? $param['scaleY'] : 0;
		}
		
		// CHECK IMAGE EXTENSIONS
		if(!in_array($this->extension, array('jpg','jpeg','bmp','png','JPG','JPEG','BMP','PNG'))) return NULL;
		// GET IMAGE
		$image = ($image) ? $image : $this->getUploadedFile();
		if($image) {
			// GET IMAGE HEIGHT AND WIDTH
			$width = ($width) ? $width : $this->imageWidth($image);
			$height = ($height) ? $height : $this->imageHeight($image);
			// GET IMAGE MAX HEIGHT AND MAX WIDTH
			$maxWidth = ($maxWidth) ? $maxWidth : $width;
			$maxHeight = ($maxHeight) ? $maxHeight : 0;
			// GET SCALE X AND Y
			$scaleX = ($scaleX) ? $scaleX : round(($maxWidth / $width),2);
			$scaleY = ($scaleY) ? $scaleY : (($maxHeight) ? round(($maxHeight / $height),2) : $scaleX );
			// GET NEW IMAGE WIDTH AND HEIGHT
			$newW = ceil($width * $scaleX);
			$newH = ceil($height * $scaleY);
			// CREATE NEW IMAGE CANVAS
			$newImage = imagecreatetruecolor($newW,$newH);
			if(!$newImage) {
				$this->setError('FAIL TO CREATE IMAGE CANVAS');
				return NULL;
			}
			// CREATE IMAGE TO RESOURCE
			if(in_array($this->extension, array('jpg','jpeg','JPG','JPEG'))) {
				$resource = imagecreatefromjpeg($image);
			} elseif(in_array($this->extension, array('bmp','BMP'))) {
				$resource = imagecreatefromwbmp($image);
			} elseif(in_array($this->extension, array('png','PNG'))) {
				$resource = imagecreatefrompng($image);
			} else {
				$this->setError('NOT AN ALLOWED IMAGE EXTENSION');
				return NULL;
			}
			if(!$resource) {
				$this->setError('FAIL TO CREATE IMAGE TO RESOURCE');
				return NULL;
			}
			// RESAMPLE IMAGE
			$result = imagecopyresampled($newImage, $resource, 0, 0, 0, 0, $newW, $newH, $width, $height);
			if(!$result) {
				$this->setError('FAIL TO RESAMPLE IMAGE');
				return NULL;
			}
			// CREATE IMAGE FILE FORMAT
			if(in_array($this->extension, array('jpg','jpeg','JPG','JPEG'))) {
				$result = imagejpeg($newImage, $image, 90);
			} elseif(in_array($this->extension, array('bmp','BMP'))) {
				$result = imagewbmp($newImage, $image, 90);
			} elseif(in_array($this->extension, array('png','PNG'))) {
				$result = imagepng($newImage, $image, 90);
			} else {
				$this->setError('NOT AN ALLOWED IMAGE EXTENSION');
				return NULL;
			}
			if(!$result) {
				$this->setError('FAIL TO CREATE IMAGE TO FORMAT');
				return NULL;
			}
			// SET IMAGE PERMISSION
			chmod($image, 0777);
			// RETURN
			return $image;
		} else {
			return NULL;
		}
	}

	private function imageHeight($image) {
		$sizes = getimagesize($image);
		return $sizes[1];
	}

	private function imageWidth($image) {
		$sizes = getimagesize($image);
		return $sizes[0];
	}
	
	////////////////////////////////////////////////////////////
	// GET REAL PATH
	// ORIGINAL: http://php.net/manual/en/function.realpath.php#112367
	// MODIFIED ON: 27/11/2014
	private function normalizePath($path) {
		$parts = array();// Array to build a new path from the good parts
		$path = str_replace('\\', '/', $path);// Replace backslashes with forwardslashes
		$path = preg_replace('/\/+/', '/', $path);// Combine multiple slashes into a single slash
		$segments = explode('/', $path);// Collect path segments
		$test = '';// Initialize testing variable
		foreach($segments as $segment) {
			if($segment != '.') {
				$test = array_pop($parts);
				if(is_null($test))
					$parts[] = $segment;
				else if($segment == '..') {
					if($test == '..')
						$parts[] = $test;
	
					if($test == '..' || $test == '')
						$parts[] = $segment;
				} else {
					$parts[] = $test;
					$parts[] = $segment;
				}
			}
		}
		return implode('/', $parts);
	}
	///////////////////////////////////////////////////////////////////////////////////////
}
?>