<?php error_reporting(0);
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
	$response = array(time());
	$parts = explode("=",$ou[0]);
	$cpu_status = hexdec($parts[1]);
	$response[]=$parts[1];
	if($cpu_status & 1){$response[] = "Under-voltage detected";}
	if($cpu_status & 2){$response[] = "Arm Freq-Cap";}
	if($cpu_status & 4){$response[] = "Throttled";}
	if($cpu_status & 8){$response[] = "Soft Temp Limit";}
	if($cpu_status & (1<<16)){$response[] = "Under-voltage occured";}
	if($cpu_status & (1<<17)){$response[] = "Freq-cap occured";}
	if($cpu_status & (1<<18)){$response[] = "Throttling occured";}
	if($cpu_status & (1<<19)){$response[] = "Soft Temp Limit occured";}
	if(!count($response)){
		$response[]="CPU OK";
	}
	$response[]=$ou[1];
	$response[]=$ou[2];
	$response[]=$ou[3];
	$response[]="Battery: ".trim(`python vbatt.py`)."V";
	echo json_encode($response);
	file_put_contents("statlog",json_encode($response).",\n",FILE_APPEND);
	die();
}
if($cmd=="l1"){
	exec("echo 1 > led");
}
if($cmd=="l0"){
	exec("echo 0 > led");
}
if($cmd=="a1"){
	file_put_contents("/home/pi/RobotPi/html/ramdisk/autocmd","GO");
}
if($cmd=="a0"){
	file_put_contents("/home/pi/RobotPi/html/ramdisk/autocmd","STOP");
}
echo json_encode($ou);

