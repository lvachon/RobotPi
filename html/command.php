<?php
$cmd = strtolower($_POST['cmd']);
$ou=array();
if($cmd=="f"){
	exec("./fwd.sh 2>&1",$ou);
}
if($cmd=="b"){
	exec("./bwd.sh 2>&1",$ou);
}
if($cmd=="l"){
	exec("./left.sh 2>&1",$ou);
}
if($cmd=="r"){
	exec("./right.sh 2>&1",$ou);
}
if($cmd=="e"){
	exec("./enable.sh 2>&1",$ou);
}
if($cmd=="d"){
	exec("./disable.sh 2>&1",$ou);
}
if($cmd=="s"){
	exec("vcgencmd get_throttled",$ou);
	exec("vcgencmd measure_temp",$ou);
	exec("vcgencmd measure_clock arm",$ou);
	exec("vcgencmd measure_volts core",$ou);
	exec("df -h /", $ou);
	exec("free -h", $ou);
}

echo json_encode($ou);
