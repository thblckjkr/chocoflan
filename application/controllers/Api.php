<?php
// This is basically a micro-api for javasrcipt
defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends CI_Controller {
   public function get( $ent ){
      $param = array(
         'user' => 'user',
         'pass' => 'pass',
         'ip' => '123.123.123.123',
         'port' => 22
      );

      $this->load->library('serverssh', $param);

      if ($this->serverssh->connection){
         echo "Sucessfully connected to server";
         $this->serverssh->getStatus();
         echo json_encode($this->serverssh->status->services);
      }else{
         $error = $this->serverssh->getError(); //Get array of errors "code" => "description"
         var_dump($error);
         $this->serverssh->logError(); //Generate a log of errors
      }
   }
}
