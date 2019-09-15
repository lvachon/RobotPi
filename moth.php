<?php
exec("echo 1 > ./html/left_enable");
exec("echo 1 > ./html/right_enable");
$go = "stop";
while(true){
	$im = imagescale(imagecreatefromjpeg("/home/pi/RobotPi/html/ramdisk/frame.jpg"),16,12);
	$maxBright = 0;
	$maxBrightX = 0;
	for($y=0;$y<6;$y++){//Top half only
		for($x=0;$x<16;$x++){
			$c = imagecolorat($im,$x,$y);
			$bright = (($c >> 16) & 255) + (($c >> 8) & 255) + ($c & 255);
			if($bright>$maxBright){
				$maxBright=$bright;
				$maxBrightX=$x;
			}
		}
	}
	imagesetpixel($im,$maxBrightX,11,imagecolorallocate($im,0,255,0));
	imagejpeg($im, "./html/small.jpg");
	$og = $go;
	if($maxBright < 255){
		$go="stop";
	}else{
		if($maxBrightX < 6){
			$go="left";
		}else{
			if($maxBrightX > 12){
				$go="right";
			}else{
				$go="fwd";
			}
		}
	}
	echo "{$maxBright}\t{$maxBrightX}\t$go\n";
	if($og!=$go){doMotors($go);}
}

function doMotors($go){
	if($go=="stop"){
		exec("echo 0 > ./html/left_fwd");
		exec("echo 0 > ./html/left_bwd");
		exec("echo 0 > ./html/right_fwd");
		exec("echo 0 > ./html/right_bwd");
	}
	if($go=="fwd"){
		exec("echo 1 > ./html/left_fwd");
		exec("echo 0 > ./html/left_bwd");
		exec("echo 1 > ./html/right_fwd");
		exec("echo 0 > ./html/right_bwd");
	}
	if($go=="left"){
		exec("echo 0 > ./html/left_fwd");
		exec("echo 0 > ./html/left_bwd");
		exec("echo 1 > ./html/right_fwd");
		exec("echo 0 > ./html/right_bwd");
	}
	if($go=="right"){
		exec("echo 1 > ./html/left_fwd");
		exec("echo 0 > ./html/left_bwd");
		exec("echo 0 > ./html/right_fwd");
		exec("echo 0 > ./html/right_bwd");
	}
}
