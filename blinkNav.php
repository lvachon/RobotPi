<?php
$frameFile = "./html/ramdisk/frame.jpg";
$frameSleep = 300;
$downsamplePower = 4;
function ledOn(){
	echo("LED ON\n");
	exec("echo 1 > ./html/led");
}
function ledOff(){
	echo("LED OFF\n");
	exec("echo 0 > ./html/led");
}
function awaitFrame($lastSize=0,$timeout=20000){
	global $frameFile,$frameSleep;
	$wait=false;
	do{
		if(!file_exists($frameFile)){$wait=true;}
		else{
			clearstatcache();
			echo("FRAME EXISTS (cur=".strval(filemtime($frameFile))."old={$lastSize}\n");
			if(filemtime($frameFile)==$lastSize){$wait=true;}
			else{$wait=false;break;}
		}
		$timeout-=$frameSleep;
		usleep($frameSleep*1000);
	}while($wait && $timeout>0);
	return filemtime($frameFile);
}
function rgb($color){
	return array("r"=>$color%255,"g"=>floor($color/255)%255,"b"=>floor($color/255));
}
function lum($color){
	$components = rgb($color);
	return 
		$components["r"]*0.2126 +
		$components["g"]*0.7152 +
		$components["b"]*0.0722; 
}
function makeDepthMap(){
	global $downsamplePower,$frameFile;
	ledOff();
	echo("GETTING DARK FRAME\n");
	$darkSize = awaitFrame(time()/*filesize($frameFile)*/);
	$darkFrame = imagecreatefromjpeg($frameFile);
	ledOn();
	echo("GETTING LIGHT FRAME\n");
	awaitFrame($darkSize);
	$lightFrame = imagecreatefromjpeg($frameFile);
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
			$lumDark = max(1,lum($darkPixel));
			$lumLight = lum($lightPixel);
			$relativeChange = ($lumLight-$lumDark)/$lumDark;
			$depthPixel = imagecolorallocate($depthMap,255*$relativeChange,255*$relativeChange,255*$relativeChange);
			imagesetpixel($depthMap,$x,$y,$depthPixel);
		}
	}
	return $depthMap;
}

while(true){
	if(file_exists("./html/ramdisk/autocmd")){
		if(file_get_contents("./html/ramdisk/autocmd")=="GO"){
			//Motors off
			echo("GO!  MAKING DEPTH MAP\n");
			$depthMap = makeDepthMap();
			echo("SAVING DEPTH MAP\n\n");
			imagejpeg($depthMap,"./html/ramdisk/robot.jpg");
			//Do stuff
		}
	}
}
