<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Overview extends CI_Controller {
    public function index(){
        $data = array(
            'title' => "Overview"
        );
        $this->load->template('overview/index', $data);
    }
}
