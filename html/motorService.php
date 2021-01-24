<?php
$oa="";
$loop = new EvTimer(0.1,0.1,function($timer){
	global $oa;
	$a = trim(file_get_contents("./ramdisk/motorCommand","r"));
	switch($a){
		case "f":
			$left_fwd=1;
			$right_fwd=1;
			$left_bwd=0;
			$right_bwd=0;
			break;
                case "b":
                        $left_fwd=0;
                        $right_fwd=0;
                        $left_bwd=1;
                        $right_bwd=1;
                        break;
                case "l":
                        $left_fwd=0;
                        $right_fwd=1;
                        $left_bwd=1;
                        $right_bwd=0;
                        break;
                case "r":
                        $left_fwd=1;
                        $right_fwd=0;
                        $left_bwd=0;
                        $right_bwd=1;
                        break;
		case "fr":
			$left_fwd=0;
                        $right_fwd=1;
                        $left_bwd=0;
                        $right_bwd=0;
                        break;
		case "fl":
			$left_fwd=0;
                        $right_fwd=1;
                        $left_bwd=0;
                        $right_bwd=0;
                        break;
                case "br":
                        $left_fwd=0;
                        $right_fwd=0;
                        $left_bwd=1;
                        $right_bwd=0;
                        break;
                case "bl":
                        $left_fwd=0;
                        $right_fwd=0;
                        $left_bwd=0;
                        $right_bwd=1;
                        break;
		case "s":
		default:
			$left_fwd=0;
			$right_fwd=0;
			$left_bwd=0;
			$right_bwd=0;
	}
	if($a!=$oa){
		exec("echo \"{$left_fwd}\" > left_fwd");
		exec("echo \"{$right_fwd}\" > right_fwd");
		exec("echo \"{$left_bwd}\" > left_bwd");
		exec("echo \"{$right_bwd}\" > right_bwd");
		echo "Executed new motor state: L{$left_fwd}{$left_bwd}, R{$right_fwd}{$right_bwd}\n";
	}
	$oa=$a;
	sleep(0.25);
});
Ev::run();
