<?php
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
	global $refStrip;
	$navStrip = imagecreatetruecolor(5,1);
	$refStrip = imagecreatetruecolor(5,1);
	imagecopyresampled($navStrip,$depthMap,0,0,0,0,5,1,imagesx($depthMap),imagesy($depthMap)/2);
	imagecopyresampled($refStrip,$depthMap,0,0,0,imagesy($depthMap)/2,5,1,imagesx($depthMap),imagesy($depthMap)/2);
	$scaleFactor = 255 / (imagecolorat($refStrip,2,0)%256);
	echo("SCALE FACTOR: {$scaleFactor}\n");
	for($x=0;$x<5;$x++){
		$oldPixel = imagecolorat($navStrip,$x,0)%256;
		$newPixel = imagecolorallocate($navStrip,min(255,$oldPixel*$scaleFactor),min(255,$oldPixel*$scaleFactor),min(255,$oldPixel*$scaleFactor));
		imagesetpixel($navStrip,$x,0,$newPixel);
	}
	return $navStrip;
}

$backCount=0;
$obsThresh=256;
function computeAvoidanceMoves($navStrip){
	global $backCount,$obsThresh;
	$farRight = imagecolorat($navStrip,4,0)%256;
	$right = imagecolorat($navStrip,3,0)%256;
	$center = imagecolorat($navStrip,2,0)%256;
	$left = imagecolorat($navStrip,1,0)%256;
	$farLeft = imagecolorat($navStrip,0,0)%256;
	echo("$farLeft,$left,$center,$right,$farRight\n");
	$move = "f";	
	$leftObs = $farLeft+$left;
	$rightObs = $farRight+$right;
	$centerObs = $center*2;
	$move="ff";
	if($leftObs>=$centerObs && $leftObs>=$rightObs && $leftObs>$obsThresh){
		$move="r";
		$backCount+=0.5;
	}
	if($rightObs>=$centerObs && $rightObs>=$leftObs && $rightObs>$obsThresh){
		$move="l";
		$backCount+=0.5;
	}
	
	if($centerObs > $obsThresh){
		$backCount++;
		if($leftObs>$rightObs){
			$move="br";
		}else{
			$move="bl";
		}
		echo("BACKCOUNT: $backCount\n");
	}
	if($move=="ff"){$backCount=max(0,$backCount-1);}
	if($backCount>3){
		if($leftObs>$rightObs){
			$d="r";
		}else{
			$d="l";
		}
		$move="bb";
		for($i=0;$i<8;$i++){
			$move.=$d;
		}
		$backCount=0;
	}
	echo("\n");
	return $move;
}
