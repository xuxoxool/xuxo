<?php
defined('BASEPATH') OR exit('No direct script access allowed');
defined('DIR_SEPARATOR') || define('DIR_SEPARATOR', '/');
defined('XUXO_VERSION') || define('XUXO_VERSION', '1.0.0');

class Home_Index_Controller extends Xuxo_Controller {
	
	public function __construct() {
		parent::__construct();
	}
	
	public function index() {
		$this->load->view('index');
	}
	
}
?>