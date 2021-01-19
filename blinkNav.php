<?php
$frameFile = "./html/ramdisk/frame.jpg";
$frameSleep = 500;
$downsamplePower = 4;
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
$lightFrame=false;
$darkFrame=false;
function makeDepthMap(){
	global $downsamplePower,$frameFile,$frameSleep,$lightFrame,$darkFrame;
	ledOff();
	echo("GETTING DARK FRAME\n");
	$darkSize = awaitFrame(filesize($frameFile));
	$darkFrame = imagecreatefromjpeg($frameFile);
	ledOn();
	echo("GETTING LIGHT FRAME\n");
	awaitFrame(filesize($frameFile));
	$lightFrame = imagecreatefromjpeg($frameFile);
	ledOff();
	echo("SCALING BY POWER $downsamplePower\n");
	$darkFrame = imagescale($darkFrame,imagesx($darkFrame)>>$downsamplePower,imagesy($darkFrame)>>$downsamplePower);
	$lightFrame = imagescale($lightFrame,imagesx($lightFrame)>>$downsamplePower,imagesy($lightFrame)>>$downsamplePower);
	
	$width = imagesx($darkFrame);
	$height = imagesy($darkFrame);
	echo("FINAL IMAGE SIZE: $width x $height\n");
	
	$depthMap = imagecreatetruecolor($width,$height);
	echo("COMPUTING DEPTH MAP\n");
	for($y=0;$y<$height;$y++){
		for($x=0;$x<$width;$x++){
			$darkPixel = imagecolorat($darkFrame,$x,$y);
			$lightPixel = imagecolorat($lightFrame,$x,$y);
			$dc = rgb($darkPixel);
			$lc = rgb($lightPixel);
			$difPixel = imagecolorallocate($depthMap,abs($dc["r"]-$lc["r"]),abs($dc["g"]-$lc["g"]),abs($dc["b"]-$lc["b"]));
			$lumDif = lum($difPixel);
			//$lumDark = max(1,lum($darkPixel));
			//$lumLight = lum($lightPixel);
			//$relativeChange = ($lumLight-$lumDark)/255;
			$depthPixel = imagecolorallocate($depthMap,$lumDif,$lumDif,$lumDif);//255*$relativeChange,255*$relativeChange,255*$relativeChange);
			imagesetpixel($depthMap,$x,$y,$depthPixel);
		}
	}
	return $depthMap;
}
function detectObstacles($depthMap){
	$navStrip = imagecreatetruecolor(5,1);
	imagecopyresampled($navStrip,$depthMap,0,0,0,0,5,1,imagesx($depthMap),imagesy($depthMap)/2);
	$green = imagecolorallocate($navStrip,0,255,0);
	$red = imagecolorallocate($navStrip,255,0,0);
	$black = imagecolorallocate($navStrip,0,0,0);
	/*for($x=0;$x<5;$x++){
		$o = imagecolorat($navStrip,$x,0)%256;
		if($o<48){imagesetpixel($navStrip,$x,0,$green);}
		if($o>76){imagesetpixel($navStrip,$x,0,$red);}
		if($o>=48 && $o<=76){imagesetpixel($navStrip,$x,0,$black);}

	}*/
	return $navStrip;
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
		//usleep($frameSleep*1000);
	}
}
$backCount=0;
while(true){
	if(file_exists("./html/ramdisk/autocmd")){
		if(file_get_contents("./html/ramdisk/autocmd")=="GO"){
			//Make Depth Map
			echo("GO!  MAKING DEPTH MAP\n");
			$depthMap = makeDepthMap();
			//Find Obstacles
			echo("DETECTING OBSTACLES\n");
                        $navStrip = detectObstacles($depthMap);
			//MoveAccordingly
			echo("COMPUTING MOVES\n");
			$farRight = imagecolorat($navStrip,4,0)%256;
			$right = imagecolorat($navStrip,3,0)%256;
			$center = imagecolorat($navStrip,2,0)%256;
			$left = imagecolorat($navStrip,1,0)%256;
			$farLeft = imagecolorat($navStrip,0,0)%256;
			echo("$farLeft,$left,$center,$right,$farRight\n");
			$move = "f";	
			/*if(floor($center>>8)%256>0){$move="ff";echo("center green,");}
			if($farRight>>16){$move = "lf";echo("far right red,");}
			if($right>>16){$move = "bl";echo("right red,");}
			if($farLeft>>16){
				echo("far left red,");
				if($move=="lb"){$move="bbl";}
				if($move=="lf"){$move="bl";}
			}
			if($left>>16){
				echo("left red,");
				if($move=="lf"){$move="rb";}
				if($move=="lb"){$move="bbr";}
			}
			if($center>>16){
				echo("center red");
				if(rand(0,2)){
					$move="bbr";
				}else{
					$move="bbl";
				}
			}*/
			$leftObs = $farLeft+2*$left;
			$rightObs = $farRight+2*$right;
			$centerObs = $center*3;
			$move="ff";
			if($leftObs>=$centerObs && $leftObs>=$rightObs && $leftObs>196){
				$move="rb";
			}
			if($rightObs>=$centerObs && $rightObs>=$leftObs && $rightObs>196){
				$move="lb";
			}
			
			if($centerObs > 196){
				$backCount++;
				if($leftObs>$rightObs){
					$move="bbr";
				}else{
					$move="bbl";
				}
				echo("BACKCOUNT: $backCount\n");
			}
			if($move=="ff"){$backCount=0;}
			if($backCount>5){
				if($leftObs>$rightObs){
					$d="r";
				}else{
					$d="l";
				}
				$move="bbbb{$d}{$d}{$d}";
				for($i=0;$i<3;$i++){
					$move.=$move;
				}
				$backCount=0;
			}
			echo("\n");
			echo("DRAWING IMAGE\n");
			//Show the human
			$width = imagesx($depthMap);
			$height = imagesy($depthMap);
			$robotImage = imagecreatetruecolor($width*3,$height+16);
			imagecopy($robotImage,$depthMap,0,0,0,0,$width,$height);
			imagecopy($robotImage,$darkFrame,$width,0,0,0,$width,$height);
		        imagecopy($robotImage,$lightFrame,$width*2,0,0,0,$width,$height);
			imagecopyresampled($robotImage,$navStrip,0,$height,0,0,$width,24,5,1);
			echo("SAVING ROBOT BRAIN\n\n");
			imagejpeg($robotImage,"./html/ramdisk/robot.jpg");
			echo("EXECUTING MOVES\n");
                        executeMoves($move);
		}else{
			echo("STOP!\n");
			sleep(5);
		}
	}else{
		echo("NO COMMANDS TO FOLLOW\n");
		sleep(5);
	}
}
