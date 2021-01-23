<?php date_default_timezone_set("UTC");
$gps = fopen("/dev/ttyACM0","r");
$satsSeen = array();
$linesSeen = array(
	"gga"=>false,
	"gsv"=>false,
	"rmc"=>false);
function saveData(){
	global $time,$lat,$lon,$speed,$alt,$satsUsed,$hdop,$fixMode,$satsSeen;
	foreach($satsSeen as $sat=>$stats){
        	if($stats["time"]<time()-20){
                                unset($satsSeen[$sat]);
                        }
                }
        $json = json_encode(array(
                "time"=>$time,
                "lat"=>$lat,
                "lon"=>$lon,
                "speed"=>$speed,
                "alt"=>$alt,
             	"used"=>$satsUsed,
                "hdop"=>$hdop,
                "fix"=>$fixMode,
                "sats"=>$satsSeen
        ));
        file_put_contents("./html/ramdisk/gpsdata",$json);
}
while($line = fgets($gps)){
	$matches=array();
	//            $GPGGA,030028.00                     ,4206.46333          ,N     ,07102.09130         ,W     ,1     ,04      ,3.08      ,36.5      ,M,-33.3       ,M,          ,*58
	preg_match('/\$GPGGA,([0-9]{2})([0-9]{2})([0-9\.]+),([0-9]{2})([0-9\.]+),([NS]),([0-9]{3})([0-9\.]+),([EW]),([0-9]),([0-9]+),([0-9\.]+),([0-9\.]+),M,([0-9\.\-]+),M,([0-9\.]*),.*/',$line,$matches);
	if(count($matches)){
		$lat=intval($matches[4])+floatval($matches[5])/60;
		if($matches[6]=="S"){$lat=-1*$lat;}
                $lon=intval($matches[7])+floatval($matches[8])/60;
                if($matches[9]=="W"){$lon=-1*$lon;}
		$fixMode = array("Invalid","GPS","DGPS")[intval($matches[10])];
		$satsUsed = intval($matches[11]);
		$hdop = floatval($matches[12]);
		$alt = floatval($matches[13]);
		$linesSeen["gga"]=true;
	}
	//            $GPGSV,3       ,2       ,11      ,14      ,51      ,160     ,19      ,17       ,72       ,35 7     ,15       ,19       ,57       ,308      ,27       ,21       ,02       ,055      ,*75
	preg_match('/\$GPGSV,([0-9]+),([0-9]+),([0-9]+),([0-9]+),([0-9]+),([0-9]+),([0-9]*),*([0-9]*),*([0-9]*),*([0-9]*),*([0-9]*),*([0-9]*),*([0-9]*),*([0-9]*),*([0-9]*),*([0-9]*),*([0-9]*),*([0-9]*),*.*/',$line,$matches);
	if(count($matches)>0){
		$numSats = intval($matches[3]);
		for($i = 0;$i<4&&4+$i*4+3<count($matches);$i++){
			$satsSeen[intval($matches[4+$i*4])]=array(
				'time'=>time(),
				'ele'=>intval($matches[4+$i*4+1]),
				'azi'=>intval($matches[4+$i*4+2]),
				'snr'=>intval($matches[4+$i*4+3])
			);
		}
//		echo $line;//$matches[1].",".$matches[2]."\n";
		if($matches[1]==$matches[2]){$linesSeen["gsv"]=true;saveData();}
	}
	//            $GPRMC,032758.00                     ,A   ,4206.46750,N ,07102.09031,W,0.198     ,          ,200121                        ,          ,      ,A*66
	preg_match('/\$GPRMC,([0-9]{2})([0-9]{2})([0-9\.]+),[AV],[0-9\.]+,[NS],[0-9\.]+,[EW],([0-9\.]+),([0-9\.]*),([0-9]{2})([0-9]{2})([0-9]{2}),([0-9\.]*),([EW]*),.*/',$line,$matches);
	if(count($matches)){
		$time = mktime(intval($matches[1]),intval($matches[2]),floatval($matches[3]),intval($matches[7]),intval($matches[6]),intval($matches[8]));
		$speed = floatval($matches[4])*0.514444;
		$course = floatval($matches[5]);
		$year = intval($matches[8])+2000;
		$magDec = floatval($matches[9]);
		if($matches[10]=="W"){$magDec*=-1;}
		$linesSeen["rmc"]=true;
	}
	if($linesSeen["rmc"]==true && $linesSeen["gsv"]==true && $linesSeen["gga"]==true){
		
		file_put_contents("gpslog", json_encode(array(
                        "time"=>$time,
                        "lat"=>round($lat*1000000)/1000000,
                        "lon"=>round(1000000*$lon)/1000000,
                        "speed"=>round(1000*$speed)/1000,
                        "alt"=>round(1000*$alt)/1000,
                        "used"=>$satsUsed,
                        "hdop"=>$hdop,
                        "fix"=>$fixMode,
                        "seen"=>count($satsSeen),
			"used"=>$satsUsed
                	))."\n",FILE_APPEND);
		$linesSeen = array(
		        "gga"=>false,
		        "gsv"=>false,
		        "rmc"=>false);
	}
}

