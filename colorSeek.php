<?php

$rawFrame=false;
$redSeek=255;
$greenSeek=0;
$blueSeek=0;
$colorTolerance=16;
function dist($a,$b){
	return ($a-$b)*($a-$b);
}
function hue($c){
	$cmax = max($c['r'],max($c['g'],$c['b']));
        $cmin = min($c['r'],min($c['g'],$c['b']));
        $delta = $cmax-$cmin;
        if($c['r']==$cmax){$hue = 60 * (($c['g']-$c['b'])/$delta);}
        if($c['g']==$cmax){$hue = 60 * (($c['b']-$c['r'])/$delta+2);}
        if($c['b']==$cmax){$hue = 60 * (($c['r']-$c['g'])/$delta+4);}
	return $hue;
}
function hue2rgb($hue){
	$s=1;
	$v=1;
	if($hue<0){$hue+=360;}
	$h = $hue/60;
	$i = floor($h);
	$f = $h-$i;
	$q[0]=$q[1]=$v*(1-$s);
	$q[2]=$v*(1-$s*(1-$f));
	$q[3]=$q[4]=$v;
	$q[5]=$v*(1-$s*$f);
	return array('r'=>255*$q[($i+4)%6],'g'=>255*$q[($i+2)%6],'b'=>255*$q[$i%6]);
}
function makeColorMap(){
	global $downsamplePower,$frameFile,$frameSleep,$rawFrame,$redSeek,$greenSeek,$blueSeek,$colorTolerance;
	static $lastSize=0;
	//ledOff();
	//UVOff();
	echo("GETTING FRAME\n");
	$lastsize = awaitFrame($lastSize);
	$rawFrame = imagecreatefromjpeg($frameFile);
	echo("SCALING BY POWER $downsamplePower\n");
	$rawFrame = imagescale($rawFrame,imagesx($rawFrame)>>$downsamplePower,imagesy($rawFrame)>>$downsamplePower);

	$width = imagesx($rawFrame);
	$height = imagesy($rawFrame);
	echo("FINAL IMAGE SIZE: $width x $height\n");
	
	$colorMap = imagecreatetruecolor($width,$height);
	echo("COMPUTING COLOR MAP\n");
	$tHue = hue(array('r'=>$redSeek,'g'=>$greenSeek,'b'=>$blueSeek));
	if($tHue>180){$tHue-=360;}
	if($tHue<-180){$tHue+=360;}
	for($y=0;$y<$height;$y++){
		for($x=0;$x<$width;$x++){
			$pixel = imagecolorat($rawFrame,$x,$y);
			$c = rgb($pixel);
			/*$scale = 255.0/(max($c['r'],max($c['g'],$c['b']))+1);
			if($c['r']<$c['g'] && $c['r']<$c['b']){$c['r']=0;}
			if($c['g']<$c['r'] && $c['g']<$c['b']){$c['g']=0;}
			if($c['b']<$c['g'] && $c['b']<$c['r']){$c['b']=0;}
			$c["r"]*=$scale;$c["g"]*=$scale;$c["b"]*=$scale;
			$dist=sqrt(dist($c['r'],$redSeek)+dist($c['g'],$greenSeek)+dist($c['b'],$blueSeek));
			*/
			$hue = hue($c);
			if($hue<-180){$hue+=360;}
			if($hue>180){$hue-=360;}
			if(abs($hue-$tHue)>$colorTolerance){
				$oc=$c;
				$c=array('r'=>0,'g'=>0,'b'=>0);
			}else{
				$c=hue2rgb($hue);
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

