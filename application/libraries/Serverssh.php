<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
Server class to connect and do commands easily
Not need a lot of things, it's to user under linux and a SSH connection on the other server
Requirements:
- PHP Version >= 5.0
- exec() [PHP function]
- fping installed on server
- fsock access on php
- ssh2 access on php

Description: With this class we create a ssh connection to a server to make commands to that on easie way
ALERT: This do an SSH connection every command
NOTE: it's necessary to use and extend class to make command. You CAN'T do it directly (Security reasons)

Example of use
$conn = new Server($params);

if ($conn->connection){
   echo "Sucessfully connected to server";
}else{
   $error = $conn->getError(); //Get array of errors "code" => "description"
   $conn->logError(); //Generate a log of errors
}

*/
class ServersSH
{
   //Public lists of errors and debugs (Info)
   public $ports = array(
      'FTP' => 21,'HTTP' => 80,'MySQL' => 3306,
      "NetShare_in" => 139, "NetShare_out" => 445
   );
   public $error = array(), $debug = array();
   public $connection;
   
   public $services = array();
   
   public $resultType = "lines";
   
   protected $username, $ip, $port;
   private $password;
   private $CI;
   
   //Send the arguments on this order: username, password, ip, port (Default 22)
   function __construct($params)
   {
      // $this->$CI =& get_instance();
      //Check if the ip can be reached
      $ping = $this->ping_check($params['ip']);
      if (!$ping){ return false; }
      
      //Check if the port it's open
      $port = $this->port_check($params['ip'], $params['port']);
      if (!$port){ return false; }
      
      //Set the login values
      $this->username = $params['user']; $this->password = $params['pass'];
      $this->ip = $params['ip']; $this->port = $params['port']; //Why here? (Because don't need to alocate memory earlier)
      
      //Try to do a simple command to check connection
      $val = $this->server_execute('echo "Hello world"');
      if (!$val ){return false;}
      
      //If not, something it's wrong
      if ($val[0] != "Hello world\n"){
         $this->error['ssh_check'] = "Cannot do ssh";
         $this->debug['ssh_check']['response'] = $val;
         $this->logError(); //Because it's strange get this kind of error, log it everytime
         return false;
      }
      
      $this->connection = true; //Connection established
   }
   
   public function getStatus($h = false)
   {
      $this->status = new stdClass();
      
      $this->services['weewx'] = array('line' => 2, 'expected' => 'active (running)');
      $this->services['mysql'] = array('line' => 2, 'expected' => 'active (running)');
      
      //Problemas para multiples salidas, juntar todos los comandos en una sola
      //Start with a beginning
      
      $comando = 'echo ">>beginning Hostname" ; ';
      $comando .= 'hostname ; ';
      
      $comando .= 'echo ">>beginning CPU" ; ';
      $comando .= 'cat /proc/loadavg| awk {\'print $1 "|" $2 "|" $3 \'} ; ';
      $comando .= 'lscpu | grep \'CPU(s)\' | awk {\'print $1 "|" $2 \'} ; ';
      
      $comando .= 'echo ">>beginning HDD" ; ';
      $comando .= 'df -P | grep -v Filesystem |awk {\'print $1 "|" $2 "|" $3 "|" $4 "|" $5\'} ; ';
      
      $comando .= 'echo ">>beginning RAM" ; ';
      $comando .= 'free -m -o | grep -v total |awk {\'print $1 "|" $2 "|" $3 "|" $4 \'} ; ';
      
      $comando .= 'echo ">>beginning Interfaces" ; ';
      $comando .= 'ifconfig | grep "inet addr:" | grep -v 127.0.0.1 | sed -e \'s/Bcast//\' | cut -d: -f2 ; ';
      
      $comando .= 'echo ">>beginning Units" ; ';
      $comando .= "netstat -an | grep 80 | grep tcp | grep -v 0.0.0.0 | grep -v 127.0.0.1 | grep -v ::: | cut -d':' -f2 | cut -d' ' -f12 | wc -l ; ";
      
      $comando .= 'echo ">>beginning SSH_active" ; ';
      $comando .= 'last | grep still | awk {\'print $3 \'} ;';
      
      $comando .= $this->cmd_services();
      $comando .= $this->cmd_procs();
      
      $result = $this->server_execute($comando);
      
      foreach ($result as $x ) {
         $y = substr($x, 0, -1);
         if( strpos($y, ">>beginning") !== false){
            $z = explode(" ", $y); $current = array_pop($z);
         }else{
            $checks[$current][] = $y;
         }
      }
      
      $this->status->hostname = $checks["Hostname"][0]; //Put hostname on status
      $this->status->connected_units = $checks["Units"][0]; //Put units on status
      
      $this->status->cpu = $this->parse_cpu_info($checks["CPU"]);
      $this->status->hdd = $this->parse_hdd_info($checks["HDD"], $h);
      $this->status->ram = $this->parse_ram_info($checks["RAM"], $h);
      
      if ( isset($checks["SSH_active"]) ){
         foreach ($checks["SSH_active"] as $ssh) {
            if (!filter_var($ssh, FILTER_VALIDATE_IP) === false) {
               $this->status->ssh_active[] = $ssh;
            }
         }
      }
      
      $this->status->services = $this->check_services($checks);
      $this->status->processes = $this->check_processes($checks);
      
      if( isset($checks["Interfaces"] )) {
         foreach ($checks["Interfaces"] as $int) {
            $this->status->interfaces[] = substr($int, 0, -2);
         }
      }

      $this->check_ports(); //Check the ports and generate a status of all
      
      return true;
   }
   
   public function ping_check($ip)
   {
      $response = exec("fping $ip -t 50");
      if ($response == "$ip is alive"){
         return true;
      }else{
         $this->debug['ping_check']['response'] = $response; $this->error['ping_check'] = "Couldn't do ping to $ip";
         return false;
      }
   }
   
   public function port_check($ip, $port)
   {
      $conn = @fsockopen($ip, $port);
      
      if ($conn){
         fclose($conn); //Close the connection established
         return true; //Connection suceded!
      }else{
         $this->error['port_check'] = "Cannot connect to $port port on $ip";
      }
      return false; //Something it's wrong
   }
   
   protected function server_execute($command)
   {
      $sshConn = @ssh2_connect($this->ip, $this->port);
      if ($sshConn){
         //The connection it's ok, authenticate
         if (  !(@ssh2_auth_password($sshConn, $this->username, $this->password) ) ) {
            //If can't authenticate not ok
            $this->error['server_execute_auth'] = 'Cannot authenticate [invalid username or password]';
            return false;
         }
      }else{
         $this->error['server_execute_conn'] = 'Cannot connect [unknown error]';
         return false;
      }
      
      $stream = ssh2_exec($sshConn, $command);
      $errors = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
      stream_set_blocking($errors, true);
      
      $this->debug['server_execute_err'][] =  stream_get_contents($errors) ;
      
      if ($this->resultType == "lines"){
         while ($line = fgets($stream)){
            $res[] = $line;
         }
      }else{
         $rstream = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
         stream_set_blocking($rstream, true);
         $res = stream_get_contents($rstream);
      }
      
      unset($sshConn);
      return $res;
   }
   
   public function getError()
   {
      //Very simple function that return's the actual connection error
      if (empty($this->error) != true){
         end($this->error); //Pointer to the end
         $key = key($this->error); //Get the key of last element
         $ret = array( "code" => $key, "description" => $this->error[$key] );
         reset($this->error);
      }else{
         $ret = array( "code" => "get_error", "description" => "No are errors to show" );
      }
      
      return $ret;
   }
   
   public function logError()
   {
      $count=0;
      $err = new stdClass();
      $err->date = date('Y-m-d H:i:s');
      $err->debug = $this->debug;
      $err->error = $this->error;
      $err->connection = array( "ip" => $this->ip , "port" =>$this->port );
      $json['log' . date('Y-m-d_H:i:s')] = $err;
      
      $data = json_encode($json) . "\n";
      do{
         $write = file_put_contents(APPPATH . "logs/error.log", $data, FILE_APPEND);
         $count++;
      }while( $write === false && $count <= 3); //Three attemps to save the data
   }
   
   protected function parse_ram_info($info, $h)
   {
      $i = 0;
      if(is_array($info)){
         foreach($info as $mem){
            $data = explode("|", $mem);
            $var[$i]['name'] = substr($data[0], 0, -1);
            
            $var[$i]['total'] = ($h) ? formatBytes($data[1] * 1024) : $data[1] * 1024;
            $var[$i]['used'] = ($h) ? formatBytes($data[2] * 1024) : $data[2] * 1024;
            $var[$i]['free'] = ($h) ? formatBytes($data[3] * 1024) : $data[3] * 1024;

            // Prevent division by 0
            $var[$i]['total'] = ($var[$i]['total'] == 0) ? 1 : $var[$i]['total'];
            $var[$i]['used_percentage'] = $var[$i]['used'] / $var[$i]['total'] * 100;
            $i++;
         }
      }else{
         //If the system not recognizes "free" bug
         $var[0]['name'] = "error"; $var[0]['total'] = 0; $var[0]['used'] = 0; $var[0]['free'] = 0; $var[0]['used_percentage'] = 0;
      }
      return $var;
   }
   
   protected function parse_hdd_info($info, $h)
   {
      $i=0;
      foreach ($info as $disk) {
         $data = explode("|", $disk);
         $var[$i]['name'] = $data[0];
         $var[$i]['total'] = ($h) ? formatBytes($data[1]) : $data[1];
         $var[$i]['used'] = ($h) ? formatBytes($data[2]) : $data[2];
         $var[$i]['free'] = ($h) ? formatBytes($data[3]) : $data[3];
         // $var[$i]['free'] = formatBytes(1024000);
         $var[$i]['used_percentage'] = substr($data[4], 0, -1);
         $i++;
      }
      return $var;
   }
   
   protected function parse_cpu_info($info)
   {
      $x = explode("|", $info[1]);
      $var["quantity"] = array_pop($x);
      $y = explode("|", $info[0]);
      $var["load_1min"] = $y[0];
      $var["load_5min"] = $y[1];
      $var["load_15min"] = $y[2];
      
      $avg = 0; foreach ($y as $z ) { $avg += $z; }
      $avg = $avg/3;
      
      $var["load_average"] = $avg;
      $var["load_percentage"] = ( $var["quantity"] > 0 ) ? $avg / $var["quantity"] * 100 : $avg;
      
      return $var;
   }
   
   public function check_ports()
   {
      foreach ($this->ports as $key => $value) {
         $status = $this->port_check($this->ip, $value);
         if($status){
            $this->status->up_ports[$value] =$key;
         }else{
            $this->status->down_ports[$value] =$key;
         }
      }
   }
   
   function check_services($checks){
      foreach ($this->services as $service => $value) {
         if ( isset($checks[ $service ]) ){
            if (strpos($checks[ $service ][ $value['line'] ], $value['expected']) !== false){
               $data[$service] = "Ok";
            }else{
               $msg = 'Expected "' . $value['expected'] . '" actual "' . $checks[$service][$value['line']] . '" on line ' . $value['line'];
               $data[$service] = array( 'Error' => $msg );
            }
         }else{
            $data[$service] = array( 'Error' => "The service doesn't exist on server");
         }
      }
      return $data;
   }
   public function check_processes($checks){
      if (isset($this->procs) && is_array($this->procs)){
         foreach ($this->procs as $proc) {
            if (isset($checks[$proc])){
               $data[$proc] = $checks[$proc][0];
            }else{
               $data[$proc] = 'Error';
            }
         }
         return $data;
      }else{
         return null;
      }
   }
   
   public function cmd_services(){
      $cmd="";
      foreach ($this->services as $service => $value) { $cmd .= "echo \">>beginning $service\" ; /etc/init.d/$service status ; "; }
      return $cmd;
   }
   
   public function cmd_procs(){
      $cmd="";
      if (isset($this->procs) && is_array($this->procs)){
         foreach ($this->procs as $proc) { $cmd .= "echo \">>beginning $proc\" ; pgrep -f \"^(/.*)?$proc\" ; "; }
      } return $cmd;
   }
}

if ( !function_exists('formatBytes') ){
   //Format bytes function
   //Taken from http://stackoverflow.com/questions/2510434/format-bytes-to-kilobytes-megabytes-gigabytes
   function formatBytes($size, $precision = 2)
   {
      if($size != 0){
         $base = log($size, 1024);
         $suffixes = array('kB', 'MB', 'GB', 'TB');   
         return round(pow(1024, $base - floor($base)), $precision) .''. $suffixes[floor($base)];
      }
      return 0;
   }
}
