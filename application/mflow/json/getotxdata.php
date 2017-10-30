<?php
/*******************************
 # getotxdata.php [mflow]
 # Author: masuwonchon@gmail.com
 #
 *******************************/

//  REFER: https://github.com/hslatman/awesome-threat-intelligence

    require_once("../config.php");
    $result['cnc_lists'] = array();
    $url="http://danger.rulez.sk/projects/bruteforceblocker/blist.php";
    $max = 5;

// Get List of OTX
    if ($config['otx'] == True ) {
	$contents = file_get_contents($url);
	$contents_l = count($contents);
	if($contents !== false){
	    $data = explode("\n",$contents);
	    $data_l = count($data);
	     for ($i=1; $i<=$max; $i++) { 
		    $tmp=explode("\t\t", $data[$i]);
		    array_push($result['cnc_lists'], $tmp[0]);
	     } 
	}
    }

// Get List of Custom
    array_push($result['cnc_lists'], "1.2.3.4");
    array_push($result['cnc_lists'], "5.6.7.8");
    array_push($result['cnc_lists'], "9.10.11.12");
    array_push($result['cnc_lists'], "162.105.131.196");
    array_push($result['cnc_lists'], "58.218.205.102");
    array_push($result['cnc_lists'], "112.90.83.112");

    $result['cnc_count'] = count($result['cnc_lists']);
    $result['status'] = 0;

    echo json_encode($result);
    die();
?>
