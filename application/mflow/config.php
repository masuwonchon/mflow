<?php
/*******************************
 # Config.php [MFLOW]
 # Author: masuwonchon@gmail.com
 #
 *******************************/
    
    /* [config parameters] */
    $config['map_center'] = "37.5665350,126.9779692";
    $config['default_flow_record_count'] = 50;
    $config['default_query_type'] = 1;
    $config['default_query_type_stat_order'] = 2; // 0: flows, 1: packets, 2: bytes [default: 2]
    $config['default_zoom_level'] = 0; // zoom level - 0: country, 1: region, 2: city, 3: host [default: 0]
    $config['auto_open_menu'] = 0;
    $config['resolve_hostnames'] = 1;
    $config['show_warnings'] = 1; 
    $config['order_flow_records_by_start_time'] = 0; //start time - 0: no, 1: yes [default: 0]
    $config['default_max_zoom_level'] = 10;

    /* [setting parameters */
    $default['cache_db'] = "db/mflow.sqlite"; // Path to the SQLite3 database file
    $default['default_geocoder_request_interval'] = 100;
    $default['refresh_interval'] = 300;
    $default['flow_count'] = 500;
   
    /* [NfSen] */
    $config['nfsen_default_sources'] = ""; 
    $config['nfsen_dir'] = "/var/cache/nfdump/flows//live/upstream1";
    
    /* [GeoLocation] */
    $config['geolocation_db'] = "MaxMind"; 
    $config['maxmind_path'] = "lib/MaxMind/GeoLiteCity.dat"; 
    
    /* [Internal traffic] */
    $config['internal_domains'] = array("192.168/16;172.16/12;10.0/8" => array("country" => "REPUBLIC OF KOREA", "region" => "SEOUL", "city" => "SEOUL"));
    $config['hide_internal_domain_traffic'] = 1; 
    $config['ignore_marker_internal_traffic_in_line_color_classification'] = 1; 

    /* [DEV] */
    $config['demo'] = False;
    $config['debug'] = False;
    $config['log_file'] = "/var/www/nfsen/plugins/mflow/log/mflow.log";
?>
