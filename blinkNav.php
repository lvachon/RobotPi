<?php
$frameFile = "./html/ramdisk/frame.jpg";
$frameSleep = 500;
$downsamplePower = 4;
echo "Utils...\n";
include 'robotUtils.php';
echo "blinkDepth\n";
include 'blinkDepth.php';
echo "uvSeek\n";
include 'uvSeek.php';
echo "tofClient\n";
include 'tofClient.php';
echo "main\n";
$minDist = 750;
while(true){
	echo "...\n";
	if(file_exists("./html/ramdisk/autocmd")){
		if(file_get_contents("./html/ramdisk/autocmd")=="GO"){
			//echo("GO!  MAKING DEPTH MAP\n");
			//$depthMap = makeDepthMap();
			//echo("DETECTING OBSTACLES\n");
            		//$navStrip = detectObstacles($depthMap);
			//echo("COMPUTING AVOIDANCE MOVES\n");
			//$moves = computeAvoidanceMoves($navStrip);
			echo "Getting Sensor Depth\n";
			$distances = getToF();
			echo("{$distances['l']},{$distances['r']}\n");
			$tofMoves = computeToFMoves($distances);
			$uvMoves="";
			if($tofMoves!="ff"){
				echo "COMPUTING UV SEEKING MOVES\n";
				$uvMap = makeUVMap();
				$uvStrip = seekSources($uvMap);
				$uvMoves = computeSeekingMoves($uvStrip);
			}
			$moves=$tofMoves;//By default avoid obstacles
			if($tofMoves!="ff"){
				if($uvMoves=="ff"){
					//obstacle detected
					//but it's a uv target in the middle
					$moves="";
					echo "FOUND TARGET\n";
				}
			}else{
				//no obstacles detected
				if(strlen($uvMoves)){
					$moves=$uvMoves;
				}
			}
			if($tofMoves=="r" && $uvMoves=="l"){
				//uv obstacle detected to the left
				$moves="l";
			}
			if($tofMoves=="l" && $uvMoves=="r"){
				//uv obstacle detected to the right
				$moves="r";
			}
			echo "$tofMoves, $uvMoves\n";
			echo("DRAWING IMAGE\n");
			renderHumanOutput(false,false,$uvMap,$uvStrip);
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
