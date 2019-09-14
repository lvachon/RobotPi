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

var_dump($ou);
