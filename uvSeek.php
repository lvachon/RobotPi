<?php
function UVOn(){
	global $frameSleep;
	echo("UV ON\n");
	exec("echo 1 > ./html/uv_led");
	usleep($frameSleep*1000);
}
function UVOff(){
	global $frameSleep;
	echo("UV OFF\n");
	exec("echo 0 > ./html/uv_led");
	usleep($frameSleep*1000);
}

$lightUVFrame=false;
$darkUVFrame=false;
function makeUVMap(){
	global $downsamplePower,$frameFile,$frameSleep,$lightUVFrame,$darkUVFrame;
	ledOff();
	echo("GETTING DARK UV FRAME\n");
	$darkSize = awaitFrame(filesize($frameFile));
	$darkUVFrame = imagecreatefromjpeg($frameFile);
	UVOn();
	echo("GETTING LIGHT UV FRAME\n");
	awaitFrame(filesize($frameFile));
	$lightUVFrame = imagecreatefromjpeg($frameFile);
	UVOff();
	echo("SCALING BY POWER $downsamplePower\n");
	$darkUVFrame = imagescale($darkUVFrame,imagesx($darkUVFrame)>>$downsamplePower,imagesy($darkUVFrame)>>$downsamplePower);
	$lightUVFrame = imagescale($lightUVFrame,imagesx($lightUVFrame)>>$downsamplePower,imagesy($lightUVFrame)>>$downsamplePower);
	
	$width = imagesx($darkUVFrame);
	$height = imagesy($darkUVFrame);
	echo("FINAL IMAGE SIZE: $width x $height\n");
	
	$uvMap = imagecreatetruecolor($width,$height);
	echo("COMPUTING UV MAP (lol)\n");
	for($y=0;$y<$height;$y++){
		for($x=0;$x<$width;$x++){
			$darkPixel = imagecolorat($darkUVFrame,$x,$y);
			$lightPixel = imagecolorat($lightUVFrame,$x,$y);
			$dc = rgb($darkPixel);
			$lc = rgb($lightPixel);
			$difPixel = imagecolorallocate($uvMap,abs($dc["r"]-$lc["r"]),abs($dc["g"]-$lc["g"]),abs($dc["b"]-$lc["b"]));
			$lumDif = lum($difPixel);
			$depthPixel = imagecolorallocate($uvMap,$lumDif,$lumDif,$lumDif);
			imagesetpixel($uvMap,$x,$y,$depthPixel);
		}
	}
	return $uvMap;
}
function seekSources($uvMap){
	global $refStripUV;
	$navStripUV = imagecreatetruecolor(5,1);
	imagecopyresampled($navStripUV,$uvMap,0,0,0,0,5,1,imagesx($uvMap),imagesy($uvMap));
	return $navStripUV;
}

$srcThresh=32;
function computeSeekingMoves($navStrip){
	global $srcThresh,$tele;
	$farRight = lum(imagecolorat($navStrip,4,0));
	$right = lum(imagecolorat($navStrip,3,0));
	$center = lum(imagecolorat($navStrip,2,0));
	$left = lum(imagecolorat($navStrip,1,0));
	$farLeft = lum(imagecolorat($navStrip,0,0));
	echo("SEEK: <$farLeft,[$left,|$center|,$right],$farRight>\n");
	$tele['seek']=array($farLeft,$left,$center,$right,$farRight);
	$leftObs = $farLeft+$left;
	$rightObs = $farRight+$right;
	$centerObs = $center*2;
	$move="f";
	if($leftObs>=$centerObs && $leftObs>=$rightObs && $leftObs>$srcThresh){
		$move="l";
	}
	if($rightObs>=$centerObs && $rightObs>=$leftObs && $rightObs>$srcThresh){
		$move="r";
	}
	if($centerObs > $leftObs && $centerObs>$rightObs && $centerObs > $srcThresh){
		$move="ff";
	}
	echo("\n");
	return $move;
}
