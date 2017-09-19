<?php
/*******************************
 # getflowdata.php [mflow]
 # Author: masuwonchon@gmail.com
 #
 *******************************/
    if (!function_exists('ReportLog')) {
        function ReportLog() {
        }   
    }   

    require_once("../config.php");
    require_once("../extensions.php");
    require_once("../../../conf.php");
    require_once("../../../nfsenutil.php");

    header("content-type: application/json");
   
    if (!session_id()) session_start();
    
    if (isset($_POST['params'])) {
        $date1 = $_POST['params']['date1'];
        $date2 = $_POST['params']['date2'];
        $hours1 = $_POST['params']['hours1'];
        $hours2 = $_POST['params']['hours2'];
        $minutes1 = $_POST['params']['minutes1'];
        $minutes2 = $_POST['params']['minutes2'];
        $flow_record_count = $_POST['params']['flow_record_count'];
        
        $nfsen_filter = $_POST['params']['nfsen_filter'];
        $nfsen_option = $_POST['params']['nfsen_option'];
        $nfsen_profile = $_POST['params']['nfsen_profile'];
        $nfsen_profile_type = $_POST['params']['nfsen_profile_type'];
        $nfsen_selected_sources = $_POST['params']['nfsen_selected_sources'];
        $aggregation_fields = $_POST['params']['aggregation_fields'];
        
        // The 'extensions' parameter is ignored by jQuery (client-side) when it's an empty array
        if (isset($_POST['params']['extensions'])) {
            $extensions = $_POST['params']['extensions'];
        } else {
            $extensions = array();
        }
    } else {
        $result['status'] = 1;
        $result['status_message'] = "No parameters provided";
        echo json_encode($result);
        die();
    }

    if ($config['demo'] == True) {
	// load sample file 
	$date1="20170829";
	$date2="20170829";
	$hours1="15";
	$hours2="15";
	$minutes1="15";
	$minutes2="15";
	$flow_record_count=$default['flow_count'];
    }

    if ($nfsen_option == 1) {
        $DIR = "/var/cache/nfdump/flows//live/upstream1";
        $DIR_DATE = date("Y-m-d");
        $files = scandir("$DIR/$DIR_DATE", SCANDIR_SORT_DESCENDING);
        $newest_file = $files[0];
        $minutes1 = substr($newest_file,-2);
        $minutes2 = $minutes1;
        $date1 = substr($newest_file, 7, -4);
        $date2 = $date1;
        $hours1 = substr($newest_file, 15, -2);
        $hours2 = $hours1;
    }   

    error_log ("[DEBUG]::".__FILE__."::".__LINE__.":: From: $date1-$hours1:$minutes1, To: $date2-$hours2:$minutes2 (flow count: $flow_record_count)\n", 3, $config['log_file']);

    // Queries
    $field_list = "%ts;%td;%sa;%da;%sp;%dp;%pr;%pkt;%byt;%fl";
    foreach ($extensions as $extension) {
        foreach ($extension->fields as $field) {
            $field_list .= ";".$field->nfdump_short;
        }
        unset($field);
    }
    unset($extension);
    
    if (mktime($hours1, $minutes1, 0, substr($date1, 4, 2), substr($date1, 6, 2), substr($date1, 0, 4))
            > mktime($hours2, $minutes2, 0, substr($date2, 4, 2), substr($date2, 6, 2), substr($date2, 0, 4))) {
        $result['status'] = 1;
        $result['status_message'] = "Invalid time range selected";
        $result['flow_record_count'] = 0;
        echo json_encode($result);
        die();
    }
    
    $run = "-R nfcapd.".$date1.$hours1.$minutes1.":nfcapd.".$date2.$hours2.$minutes2." -Nq -6 -o \"fmt:".$field_list."\"";

    if ($nfsen_option == 0) {
        $run .= " -c ".$flow_record_count;
    } else {
        switch (intval($_POST['params']['nfsen_stat_order'])) {
            case 0:     $nfsen_stat_order = "flows";
                        break;
                        
            case 1:     $nfsen_stat_order = "packets";
                        break;
                                    
            case 2:     $nfsen_stat_order = "bytes";
                        break;
                        
            default:    break;
        }
        $run .= " -n ".$flow_record_count." -s record/".$nfsen_stat_order;
    }
    
    // Sorting by flow record start time
    if ($nfsen_option == 0 && $config['order_flow_records_by_start_time'] == 1) {
        if ($_SESSION['mflow']['nfdump_version'] && intval(str_replace(".", "", $nfdump_version)) >= 168) {
            $run .= " -O tstart";
        } else {
            $run .= " -m";
        }
    }
    
    // Aggregation fields
    $selected_aggregation_fields = array();
    foreach ($aggregation_fields as $key => $value) {
        $value = intval($value);
        if ($value === 1) {
            $formatted_aggregation_field = $key;
            
            // Remove 'aggr_'
            $formatted_aggregation_field = str_replace("aggr_", "", $formatted_aggregation_field);
            
            // Remove underscores
            $formatted_aggregation_field = str_replace("_", "", $formatted_aggregation_field);
            
            array_push($selected_aggregation_fields, $formatted_aggregation_field);
        }
    }
    
    if (sizeof($selected_aggregation_fields) > 0) {
        $aggregation_fields_string = "-A ".implode(",", $selected_aggregation_fields);
        $run .= " -A ".implode(",", $selected_aggregation_fields);
    }

    $cmd_opts['args'] = "-T $run";
    $cmd_opts['profile'] = $nfsen_profile;
    $cmd_opts['type'] = $nfsen_profile_type;
    $cmd_opts['srcselector'] = implode(":", $nfsen_selected_sources);
    $cmd_opts['filter'] = array($nfsen_filter);
    
    $result = array();

    if (!isset($_SESSION['profile'])) {
        $result['status'] = 1;
        $result['status_message'] = "NfSen session has expired";
        $result['flow_record_count'] = 0;
        echo json_encode($result);
        die();
    }

    // Execute NfSen query
    if ($config['demo'] == True)
	$cmd_opts['mode'] = "demo";

    $cmd_out = nfsend_query("run-nfdump", $cmd_opts);
    
    $result['query'] = "nfdump ".$cmd_out['arg'];
    
    /* For debugging nfdump-related errors */

    if ($config['debug'] == True) {
	error_log ("[DEBUG]::".__FILE__."::".__LINE__.":: cmd_opts\n", 3, $config['log_file']);
//	error_log (print_r($cmd_opts, TRUE)."\n", 3, $config['log_file']);

	error_log ("[DEBUG]::".__FILE__."::".__LINE__.":: cmd_out\n", 3, $config['log_file']);
//	error_log (print_r($cmd_out, TRUE)."\n", 3, $config['log_file']); //nfdump result array

	error_log ("[DEBUG]::".__FILE__."::".__LINE__.":: result\n", 3, $config['log_file']);
	error_log (print_r($result['query'], TRUE)."\n", 3, $config['log_file']);
    }

    if (isset($cmd_out['nfdump']) && $cmd_out['exit'] > 0) {
        $result['status'] = 1;
                
        if (count($cmd_out['nfdump']) > 0) {
            if ($cmd_out['nfdump'][0] == "Killed") {
                $result['status_message'] = "Flow data query process was killed";
            } else if (strpos($cmd_out['nfdump'][0], 'File not found') !== false) {
                $result['status_message'] = "Flow data file could not be found (".$cmd_out['nfdump'][0].")";
            } else {
                $result['status_message'] = "Syntax error in flow filter (".$cmd_out['nfdump'][0].")";
            }
        }

        $result['flow_record_count'] = 0;
        echo json_encode($result);
        die();
    } else if (isset($_SESSION['error']) && isset($_SESSION['error'][0])) {
        $result['status'] = 1;
        $result['status_message'] = "Profile error (".$_SESSION['error'][0].")";
        $result['flow_record_count'] = 0;
        echo json_encode($result);
        die();
    } else if (!isset($cmd_out['nfdump']) || sizeof($cmd_out['nfdump']) == 0) {
        $result['status'] = 1;
        $result['status_message'] = "No flow records in result set";
        $result['flow_record_count'] = 0;
        echo json_encode($result);
        die();
    } else if (isset($cmd_out['nfdump'][1]) && $cmd_out['nfdump'][1] == "Empty file list. No files to process") {
        $result['status'] = 1;
        $result['status_message'] = "Unknown error";
        $result['flow_record_count'] = 0;
        echo json_encode($result);
        die();
    }

    $result['flow_record_count'] = sizeof($cmd_out['nfdump']);
    $result['flow_data'] = array();

    foreach ($cmd_out['nfdump'] as $line) {

        if ($line == "No matched flows") {
            $result['status'] = 1;
            $result['status_message'] = "No flow records in result set";
            $result['flow_record_count'] = 0;
            echo json_encode($result);
            die();
        } else if (strlen($line) > 0 && !ctype_digit(substr($line, 0, 1))) { // The additional strlen is just required as ctype_digit returns true for an empty string before PHP 5.1
            $result['flow_record_count']--;
            
            // Line should be skipped in case it is not a flow record (such as nfdump map errors to debug messages).
            continue;
        }
        
        // Remove unused characters
        for ($i = 0; $i < strlen($line); $i++) {
            if (ord(substr($line, $i, 1)) < 32) {
                $line = substr_replace($line, '', $i, 1);
            }
        }
        if ($line == '') continue;
        
        $line_array = explode(";", $line);
    
        $record = new FlowRecord();
        $record->start_time = trim($line_array[0]);
        $record->duration = floatval(trim($line_array[1]));
        $record->ip_src = trim($line_array[2]);
        $record->ip_dst = trim($line_array[3]);
        $record->port_src = floatval(trim($line_array[4]));
        $record->port_dst = floatval(trim($line_array[5]));
        $record->protocol = intval(trim($line_array[6]));
        $record->packets = intval(trim($line_array[7]));
        $record->octets = intval(trim($line_array[8]));
        $record->flows = intval(trim($line_array[9]));
        
        // Index of the field in each nfdump line
        $field_index = 10;
        foreach ($extensions as $extension) {
            foreach ($extension->fields as $field) {
                // Remove dollar-sign (nfdump output format notation)
                $key = substr($field->nfdump_short, 1);
                
                // $record->$key = intval(trim($line_array[$field_index]));
                $record->$key = trim($line_array[$field_index]);
                $field_index++;
            }
            unset($field);
        }
        unset($extension);
    
        array_push($result['flow_data'], $record);
    }
    unset($line);
    
    nfsend_disconnect();

    $result['status'] = 0;
    
    echo json_encode($result);
    die();
    
    class FlowRecord {
        public $start_time;
        public $duration;
        public $ip_src;
        public $ip_dst;
        public $port_src;
        public $port_dst;
        public $protocol;
        public $packets;
        public $octets;
        public $flows; // is not a NetFlow field, but used by nfdump for aggregation
    }

?>
