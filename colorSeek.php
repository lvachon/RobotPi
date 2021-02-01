<?php

$rawFrame=false;
$redSeek=255;
$greenSeek=0;
$blueSeek=0;
$colorTolerance=16;
function makeColorMap(){
	global $downsamplePower,$frameFile,$frameSleep,$rawFrame,$redSeek,$greenSeek,$blueSeek,$colorTolerance;
	ledOff();
	UVOff();
	echo("GETTING FRAME\n");
	$size = awaitFrame(filesize($frameFile));
	$rawFrame = imagecreatefromjpeg($frameFile);
	echo("SCALING BY POWER $downsamplePower\n");
	$rawFrame = imagescale($rawFrame,imagesx($rawFrame)>>$downsamplePower,imagesy($rawFrame)>>$downsamplePower);

	$width = imagesx($rawFrame);
	$height = imagesy($rawFrame);
	echo("FINAL IMAGE SIZE: $width x $height\n");
	
	$colorMap = imagecreatetruecolor($width,$height);
	echo("COMPUTING COLOR MAP\n");
	for($y=0;$y<$height;$y++){
		for($x=0;$x<$width;$x++){
			$pixel = imagecolorat($rawFrame,$x,$y);
			$c = rgb($pixel);
			$scale = 255.0/max($c['r'],max($c['g'],$c['b']));
			$c["r"]*=$scale;$c["g"]*=$scale;$c["b"]*=$scale;
			if(!(
				abs($c['r']-$redSeek)<$colorTolerance &&
				abs($c['g']-$greenSeek)<$colorTolerance &&
				abs($c['b']-$blueSeek)<$colorTolerance
			)){
				$c=array('r'=>0,'g'=>0,'b'=>0);
			}
			$fullPixel = imagecolorallocate($colorMap,$c["r"],$c["g"],$c["b"]);
			imagesetpixel($colorMap,$x,$y,$fullPixel);
		}
	}
	return $colorMap;
}
function seekColorSources($colorMap){
	$navStrip = imagecreatetruecolor(5,1);
	imagecopyresampled($navStrip,$colorMap,0,0,0,0,5,1,imagesx($colorMap),imagesy($colorMap));
	return $navStrip;
}

