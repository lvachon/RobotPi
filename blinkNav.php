<?php
$frameFile = "./html/ramdisk/frame.jpg";
$settings = json_decode(file_get_contents("./html/botSettings.json"));
$frameSleep = 500;//ms
$minDist = 750;//mm
$downsamplePower = 4;//width/2^x
$tele = array();
echo "Utils...\n";
include 'robotUtils.php';
//echo "blinkDepth\n";
//include 'blinkDepth.php';
echo "uvSeek\n";
include 'uvSeek.php';
echo "tofClient\n";
include 'tofClient.php';
echo "main\n";
while(true){
	echo "...\n";
	if(file_exists("./html/ramdisk/autocmd")){
		readSettings();
		if(file_get_contents("./html/ramdisk/autocmd")=="GO"){
			//echo("GO!  MAKING DEPTH MAP\n");
			//$depthMap = makeDepthMap();
			//echo("DETECTING OBSTACLES\n");
            		//$navStrip = detectObstacles($depthMap);
			//echo("COMPUTING AVOIDANCE MOVES\n");
			//$moves = computeAvoidanceMoves($navStrip);
			echo "Getting Sensor Depth\n";
			$distances = getToF();
			$tele['distances']=array($distances['l'],$distances['r']);
			echo("{$distances['l']},{$distances['r']}\n");
			$tofMoves = computeToFMoves($distances);
			$tele['tofMoves']=$tofMoves;
			$uvMoves="";
			if($tofMoves!="ff"){
				echo "COMPUTING UV SEEKING MOVES\n";
				$uvMap = makeUVMap();
				$uvStrip = seekSources($uvMap);
				$uvMoves = computeSeekingMoves($uvStrip);
			}
			$tele['uvMoves']=$uvMoves;
			$moves=$tofMoves;//By default avoid obstacles
			if($tofMoves!="ff"){
				$tele['phase']="Avoid";
				if($uvMoves=="ff"){
					//obstacle detected
					//but it's a uv target in the middle
					$moves="";
					echo "FOUND TARGET\n";
					$tele['phase']="UV Found";
				}
			}else{
				//no obstacles detected
				$tele['phase']="Travel";
				if(strlen($uvMoves)){
					$tele['phase']="Seek";
					$moves=$uvMoves;
				}
			}
			if($tofMoves=="r" && $uvMoves=="l"){
				//uv obstacle detected to the left
				$tele['phase']="Target Left";
				$moves="l";
			}
			if($tofMoves=="l" && $uvMoves=="r"){
				//uv obstacle detected to the right
				$tele['phase']="Target Right";
				$moves="r";
			}
			$tele['moves']=$moves;
			echo "$tofMoves, $uvMoves\n";
			if($uvMap && $uvStrip){
				echo("DRAWING IMAGE\n");
				renderHumanOutput(false,false,$uvMap,$uvStrip);
			}
			echo("EXECUTING MOVES\n");
			executeMoves($moves);
		}else{
			$tele['phase']="Stop";
			echo("STOP!\n");
			sleep(5);
		}
	}else{
		$tele['phase']="Waking up?";
		echo("NO COMMANDS TO FOLLOW\n");
		sleep(5);
	}
	writeTelemetry();
}
