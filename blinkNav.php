<?php
$frameFile = "./html/ramdisk/frame.jpg";
$frameSleep = 500;
$downsamplePower = 4;
include 'robotUtils.php';
include 'blinkDepth.php';
include 'uvSeek.php';
include 'tofClient.php';
$minDist = 750;
while(true){
	if(file_exists("./html/ramdisk/autocmd")){
		if(file_get_contents("./html/ramdisk/autocmd")=="GO"){
			//echo("GO!  MAKING DEPTH MAP\n");
			//$depthMap = makeDepthMap();
			//echo("DETECTING OBSTACLES\n");
            		//$navStrip = detectObstacles($depthMap);
			//echo("COMPUTING AVOIDANCE MOVES\n");
			//$moves = computeAvoidanceMoves($navStrip);
			$distances = getToF();
			echo("{$distances['l']},{$distances['r']}\n");
			$moves = computeToFMoves($distances);
			if($moves=="ff" && false){
				echo "COMPUTING UV SEEKING MOVES\n";
				$uvMap = makeUVMap();
				$uvStrip = seekSources($uvMap);
				$moves = computeSeekingMoves($uvStrip);
			}
			//echo("DRAWING IMAGE\n");
			//renderHumanOutput($depthMap,$navStrip,$uvMap,$uvStrip);
			echo("EXECUTING MOVES\n");
			executeMoves($moves);
		}else{
			echo("STOP!\n");
			sleep(5);
		}
	}else{
		echo("NO COMMANDS TO FOLLOW\n");
		sleep(5);
	}
}
