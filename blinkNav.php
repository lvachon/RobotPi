<?php
$frameFile = "./html/ramdisk/frame.jpg";
$frameSleep = 300;
$downsamplePower = 4;
function ledOn(){
	exec("echo 1 > ./html/led");
}
function ledOff(){
	exec("echo 0 > ./html/led");
}
function awaitFrame($lastTime=0,$timeout=20000){
	$wait=false;
	do{
		if(!file_exists($frameFile)){$wait=true;}
		else{
			if(filemtime($frameFile)<=$lastTime){$wait=true;}
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
	ledOff();
	awaitFrame(time());
	$darkFrame = imagecratefromjpeg($frameFile);
	ledOn();
	awaitFrame(time());
	$lightFrame = imagecreatefromjpeg($frameFile);

	$darkFrame = imagescale($darkFrame,imagesx($darkFrame)>>$downsamplePower,imagesy($darkFrame)>>$downsamplePower);
	$lightFrame = imagescale($lightFrame,imagesx($lightFrame)>>$downsamplePower,imagesy($lightFrame)>>$downsamplePower);

	$width = imagesx($darkFrame);
	$height = imagesy($darkFrame);

	$depthMap = imagecreatetruecolor($width,$height);

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
			$depthMap = makeDepthMap();
			imagejpeg($depthMap,"./html/ramdisk/robot.jpg");
			//Do stuff
		}
	}
}
