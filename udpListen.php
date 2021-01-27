<?php
function sig_handler($signo){
	global $sock;
	echo "closing socket\n";
	socket_close($sock);
}

$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP); 
socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, array("sec"=>5, "usec"=>0));
socket_bind($sock,'127.0.0.1',8888);
echo "Listening...";

$from = '';
$port = 0;

while(true) {
  $ret = @socket_recvfrom($sock, $buf, 20, 0, $from, $port);
  if($ret === false) {echo "No Messages\n";}
  else{echo "Message : < $buf > , $ip : $port \n";}
}
pcntl_signal(SIGTERM, "sig_handler");
socket_close($sock);
