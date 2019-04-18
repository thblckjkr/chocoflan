<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Servers extends CI_Controller {
   public function index( $ent ){
      $data = array(
         'title' => "Overview",
         'data' => array ('server' => $ent)
      );
      $this->load->template('servers/index', $data);
   }
}
