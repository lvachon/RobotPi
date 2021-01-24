<?php
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
	return array("r"=>$color%256,"g"=>floor($color/255)%256,"b"=>floor($color/65536));
}
function lum($color){
	$components = rgb($color);
	$cmax = max($components["r"],max($components["g"],$components["b"]));
	$cmin = min($components["r"],min($components["g"],$components["b"]));
	return ($cmin+$cmax)/2;
	return
		$components["r"]*0.3333 +
		$components["g"]*0.3333 +
		$components["b"]*0.3333; 
}
function renderHumanOutput($depthMap,$navStrip,$uvMap,$uvStrip){
	global $darkFrame,$lightFrame,$refStrip,$darkUVFrame,$lightUVFrame;
	$width = imagesx($depthMap);
	$height = imagesy($depthMap);
	$robotImage = imagecreatetruecolor($width*3,$height*2+24);
	imagecopy($robotImage,$depthMap,0,0,0,0,$width,$height);
	imagecopy($robotImage,$darkFrame,$width,0,0,0,$width,$height);
	imagecopy($robotImage,$lightFrame,$width*2,0,0,0,$width,$height);
	imagecopy($robotImage,$uvMap,0,$height,0,0,$width,$height);
	imagecopy($robotImage,$darkUVFrame,$width,$height,0,0,$width,$height);
	imagecopy($robotImage,$lightUVFrame,$width*2,2*$height,0,0,$width,$height);
	imagecopyresampled($robotImage,$navStrip,0,$height*2,0,0,$width*1.5,12,5,1);
	imagecopyresampled($robotImage,$refStrip,0,$height*2+12,0,0,$width*1.5,12,5,1);
	imagecopyresampled($robotImage,$uvStrip,$width*1.5,$height*2,0,0,$width*1.5,24,5,1);

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