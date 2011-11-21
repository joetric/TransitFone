<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Welcome extends CI_Controller {
	public function index()
	{
		if(isset($_GET['route'])) {
			header('Location: septa/route/'.$_GET['route']); // redirect on form submit
		}
		$this->load->view('home');
	}
}