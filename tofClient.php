<?php
$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP); 
socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, array("sec"=>0, "usec"=>100000));
socket_bind($sock,'127.0.0.1',8888);
function getToF(){
  global $sock;
  $buf='';
  $from = '';
  $port = 0;
  do{
    $ranges = array();
    while($ret = @socket_recvfrom($sock, $buf, 20, 0, $from, $port)){
  	echo $buf."\n";
	$ranges = explode(",",$buf);
    }
  }while(count($ranges)<2);
  //if($ret === false) {return array('l'=>0,'r'=>'0');}
  //echo "$buf\n";
  //$ranges = explode(",",$buf);
  return array('l'=>$ranges[0],'r'=>$ranges[1]);
}
$backCount=0;
$backMove = "r";
$backLimit=3;
function computeToFMoves($distances){
	global $backCount,$minDist,$backMove,$tele,$backLimit;
	$moves = "ff";
	if($distances['l']<$distances['r'] && $distances['l']<$minDist){
        	if($distances['l']<$minDist/2){
                        $moves="br";
			$backCount+=1;
                }else{
                        $moves="r";
			$backCount+=0.5;
                }
        }
        if($distances['r']<$distances['l'] && $distances['r']<$minDist){
                if($distances['r']<$minDist/2){
                        $moves="bl";
			$backCount+=1;
                }else{
                        $moves="l";
			$backCount+=0.5;
                }
        }
	if($moves=="ff"){
		$backCount=max(0,$backCount-1);
	}else{
		if($backCount>=$backLimit){
			$moves=$backMove;
			$backCount=3;
		}else{
			$backMove = str_replace("b","",$moves);
		}
	}
	$tele['backCount']=$backCount;
	return $moves;
}
