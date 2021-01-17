<?php
//$calibration = array('0'=>0.008, '3.3'=>0.10, '5'=>0.011);
$ot = 0;
$ou = "1\n";
//Wait for a falling edge
while($ou=="1\n"){
        $ou=`cat vbatt`;
};
var_dump($ou);
//Start timer at next rising edge
while($ou=="0\n"){
	$ou=`cat vbatt`;
};
$ot=microtime(true);
$dt=0;
//Stop timer at next falling edge
while($ou=="1\n"){
	$ou=`cat vbatt`;
};
$dt=microtime(true)-$ot;
echo $dt."---".($dt-0.008)/0.0006;
