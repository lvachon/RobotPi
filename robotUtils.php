<?php
//Init LoRa
exec("stty -F /dev/serial0 115200");
exec("printf 'AT+PARAMETER=7,7,1,6\\r\\n' > /dev/serial0");
function getGPS(){
	return json_decode(file_get_contents("./html/ramdisk/gpsdata"));
}

function ledOn(){
        global $frameSleep;
        echo("LED ON\n");
        exec("echo 1 > ./html/led");
        usleep($frameSleep*1000);
}
function ledOff(){
        global $frameSleep;
        echo("LED OFF\n");
        exec("echo 0 > ./html/led");
        usleep($frameSleep*1000);
}

function awaitFrame($lastSize=0,$timeout=20000){
	global $frameFile,$frameSleep;
	$wait=false;
	do{
		if(!file_exists($frameFile)){$wait=true;}
		else{
			clearstatcache();
			echo("FRAME EXISTS (cur=".strval(filesize($frameFile))."old={$lastSize}\n");
			if(filesize($frameFile)==$lastSize){$wait=true;}
			else{$wait=false;break;}
		}
		$timeout-=$frameSleep;
		usleep($frameSleep*1000);
	}while($wait && $timeout>0);
	return filesize($frameFile);
}
function rgb($color){
	return array("b"=>$color%256,"g"=>floor($color/256)%256,"r"=>floor($color/65536));
}
function lum($color){
	$components = rgb($color);
	$cmax = max($components["r"],max($components["g"],$components["b"]));
	$cmin = min($components["r"],min($components["g"],$components["b"]));
	return $cmax;
	return ($cmin+$cmax)/2;
	return
		$components["r"]*0.3333 +
		$components["g"]*0.3333 +
		$components["b"]*0.3333; 
}
function renderHumanOutput($navMap,$navStrip){
	$width = imagesx($navMap);
	$height = imagesy($navMap);
	$robotImage = imagecreatetruecolor($width,$height+24);
	imagecopy($robotImage,$navMap,0,0,0,0,$width,$height);
	imagecopyresampled($robotImage,$navStrip,0,$height,0,0,$width,24,5,1);
	echo("SAVING ROBOT BRAIN\n\n");
	imagejpeg($robotImage,"./html/ramdisk/robot.jpg");
}
function executeMoves($move){
	global $frameSleep;
	for($i=0;$i<strlen($move);$i++){
		$m = substr($move,$i,1);
		echo("MOVE: {$m}\n");
		switch($m){
			case "f":
				exec("cd html;./fwd.sh 2>&1");
				break;
			case "l":
                exec("cd html;./left.sh 2>&1");
                break;
			case "r":
                exec("cd html;./right.sh 2>&1");
                break;
			case "b":
                exec("cd html;./bwd.sh 2>&1");
                break;
		}
	}
}
function writeTelemetry(){
	global $tele;
	$tele['time']=time();
	file_put_contents("./html/ramdisk/telemetry",json_encode($tele));
	$lorastring="MODE: ${tele['phase']}";
	$lorastring.=";MOVES: ${tele['moves']}";
	$pos = getGPS();
	$lorastring.=";POS:".strval(floor($pos['lat']*1000)/10000).",".strval(floor($pos['lon']*1000)/1000);
	$lorastring.=";TOF:{$tele['distances'][0]},{$tele['distances'][1]}";
	$lorastring.=";SEEK:".implode(',',$tele['seek']);
	echo("printf 'AT+SEND=0,".strlen($lorastring).",".$lorastring."\\r\\n' > /dev/serial0");
	exec("printf 'AT+SEND=0,".strlen($lorastring).",".$lorastring."\\r\\n' > /dev/serial0");
}

function readSettings(){
	global $minDist,$obsThresh,$frameSleep,$downsamplePower,$backLimit,$thresh,$redSeek,$blueSeek,$greenSeek,$colorTolerance,$navMode;
	static $lastMod = 0;
	clearstatcache();
	if(filemtime("./html/botSettings")<=$lastMod){return;}
	echo "NEW SETTINGS";
	$lastMod = filemtime("./html/botSettings");
	$thresh['settingsTime']=$lastMod;
	$settings = json_decode(file_get_contents("./html/botSettings"));
	if(isset($settings->minDist)){$minDist=intval($settings->minDist);}
	if(isset($settings->srcThresh)){$obsThresh=intval($settings->srcThresh);}
	if(isset($settings->frameSleep)){$frameSleep=intval($settings->frameSleep);}
	if(isset($settings->downsamplePower)){$downsamplePower=intval($settings->downsamplePower);}
	if(isset($settings->backLimit)){$backLimit=intval($settings->backLimit);}
	if(isset($settings->redSeek)){$redSeek=intval($settings->redSeek);}
	if(isset($settings->blueSeek)){$blueSeek=intval($settings->blueSeek);}
	if(isset($settings->greenSeek)){$greenSeek=intval($settings->greenSeek);}
	if(isset($settings->colorTolerance)){$colorTolerance=intval($settings->colorTolerance);}
	if(strlen($settings->navMode)){$navMode=$settings->navMode;}

}


