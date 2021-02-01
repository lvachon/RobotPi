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
echo "colorSeek\n";
include 'colorSeek.php';
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
			$seekMoves="";
			echo "[[[[[[[[[[[[[[[[[{$navMode}]]]]]]]]]]]]]]]]]]]]\n";
			if($tofMoves!="ff" && $navMode=="UV"){
				echo "COMPUTING UV SEEKING MOVES\n";
				$navMap = makeUVMap();
				$navStrip = seekSources($navMap);
				$seekMoves = computeSeekingMoves($navStrip);
			}
			if($navMode=="Color"){
				echo "COMPUTING COLOR SEEKING MOVES\n";
				$navMap = makeColorMap();
				$navStrip = seekColorSources($navMap);
				$seekMoves = computeSeekingMoves($navStrip);
			}
			$tele['seekMoves']=$seekMoves;
			$moves=$tofMoves;//By default avoid obstacles
			if($tofMoves!="ff"){
				$tele['phase']="Avoid";
				if($seekMoves=="ff"){
					//obstacle detected but it's a seek target in the middle
					$moves="";
					echo "FOUND TARGET\n";
					$tele['phase']="Target Found";
				}
			}else{
				//no obstacles detected
				$tele['phase']="Travel";
				if(strlen($seekMoves)){
					$tele['phase']="Seek";
					$moves=$seekMoves;
				}
			}
			if($tofMoves=="r" && $seekMoves=="l"){
				//target obstacle detected to the left
				$tele['phase']="Target Left";
				$moves="l";
			}
			if($tofMoves=="l" && $seekMoves=="r"){
				//target obstacle detected to the right
				$tele['phase']="Target Right";
				$moves="r";
			}
			$tele['moves']=$moves;
			echo "$tofMoves, $seekMoves\n";
			if($navMap && $navStrip){
				echo("DRAWING IMAGE\n");
				renderHumanOutput($navMap,$navStrip);
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
