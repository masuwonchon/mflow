<?php
/*******************************
 # getotxdata.php [mflow]
 # Author: masuwonchon@gmail.com
 #
 *******************************/

//  REFER: https://github.com/hslatman/awesome-threat-intelligence

    require_once("../config.php");
    $result['cnc'] = array();
    $result['malware'] = array();
    $result['ddos'] = array();
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
		    array_push($result['cnc'], $tmp[0]);
	     } 
	}
    }

// Get List of Custom
    array_push($result['cnc'], "112.90.83.112");
    array_push($result['malware'], "104.111.199.146");
    array_push($result['ddos'], "203.188.200.67");

    $result['cnc_cnt'] = count($result['cnc']);
    $result['malware_cnt'] = count($result['malware']);
    $result['ddos_cnt'] = count($result['ddos']);
    $result['status'] = 0;

    echo json_encode($result);
    die();
?>
