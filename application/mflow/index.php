<?php
/*******************************
 # index.php [MFLOW]
 # Author: masuwonchon@gmail.com
 #
 *******************************/
     header("Content-type: text/html; charset=utf-8");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>MFLOW</title>
    <link type="text/css" rel="stylesheet" href="lib/jquery/css/start/jquery-ui-1.10.4.custom.min.css" />
    <link type="text/css" rel="stylesheet" href="css/mflow.css" />
    <script type="text/javascript" src="https://maps.google.com/maps/api/js?key=AIzaSyAX5-RzfBfreiLLyvxEtbZpsBtpT_3aQa4"></script>
    <script type="text/javascript" src="lib/jquery/js/jquery-1.9.1.min.js"></script>
    <script type="text/javascript" src="lib/jquery/js/jquery-ui-1.10.4.custom.min.js"></script>
    <script type="text/javascript" src="lib/jquery_browser/jquery.browser.js"></script> 
    <script type="text/javascript" src="lib/json2/json2.js"></script> 
    <script type="text/javascript" src="lib/jquery_cookie/jquery.cookie.js"></script> 
    <script type="text/javascript" src="lib/jquery_multiselect/jquery.multiselect.min.js"></script> 
    <script type="text/javascript" src="lib/jquery_timepicker/jquery-ui-timepicker-addon.js"></script> 
    <script type="text/javascript" src="js/dialogs.js"></script>
    <script type="text/javascript" src="js/events.js"></script>
    <script type="text/javascript" src="js/maputil.js"></script>
    <script type="text/javascript" src="js/panel.js"></script>
    <script type="text/javascript" src="js/util.js"></script>
    <script>

        var ajax_error = 0;
        $.cookie.json = true;
        var config;
        var constants;
        var extensions;
        var session_data;
        
        var auto_refresh_handle;
        var loading_message_timeout_handle;
        var store_session_data_handle;
        
        var flow_data;
        var geolocation_data;
        var geocoder_data_server;
        var geocoder_data_client;
        var geocoder_request_server;
        var geocoder_request_client;
        var reverse_geocoder_request;
        var resolved_hostnames;
        
        var error_dialog_queue = [];
        var warning_dialog_queue = [];
        
        var lines;
        var markers;
        var map;
        var geocoder = new google.maps.Geocoder();
        var info_window = new google.maps.InfoWindow();

        var zoom_levels = {
            0:  'country',
            1:  'region',
            2:  'city',
            3:  'host'
        };
        
        var global_line_minima = {
            'country':  -1,
            'region':   -1,
            'city':     -1
        };

        var global_line_maxima = {
            'country':  -1,
            'region':   -1,
            'city':     -1
        };
        
        jQuery.ajaxSetup({
            cache: false,
            dataType: 'json',
            proccessData: false,
            type: 'POST'
        });
        
        $(document).ajaxError(function(event, jqXHR, ajaxSettings, exception) {
            ajax_error = 1;
            $(document).trigger('loading_cancelled');
            
            if (jqXHR.status == 0) {
                show_error(800, "[ERROR] Could not connect to the server. Please check your network connectivity.");
            } else if (jqXHR.status == 404) {
                show_error(800, "[ERROR] The requested page could not be found (HTTP 404).");
            } else if (jqXHR.status == 500) {
                show_error(800, "[ERROR] Internal server error (HTTP 500)");
            } else if (exception === 'parsererror') {
                show_error(800, "[ERROR] The requested JSON document could not be parsed.");
            } else if (exception === 'timeout') {
                show_error(800, "[ERROR] Timeout error.");
            } else if (exception === 'abort') {
                show_error(800, "[ERROR] The AJAX request has been aborted.");
            } else {
                show_error(800);
            }
        });
        
        // Retrieve errors from server
        var cookie_value = get_cookie_value('mflow', 'errors_retrieved');
        if (cookie_value == undefined || cookie_value == 0) {
            $.ajax({
                url: 'json/geterrors.php',
                success: function(data) {
                    if (data.status == 0) { 
			// Success
                        if (data.error_codes.length == 0) {
                            /* Set cookie so that errors are not retrieved
                             * another time within the same session
                             */
                            update_cookie_value('mflow', 'errors_retrieved', 1);
                        } else {
                            $('#error_messages').empty();
                            $.each(data.error_codes, function (i, error_code) {
                                var error = $('<p />', { 'id': 'error-' + i });
                                var icon = $('<span />', { 'class': 'ui-icon ui-icon-alert' });
                                var message = $('<div />', { 'id': 'error-text' });
                                message.html(get_backend_error_description(error_code));
            
                                error.append(icon);
                                error.append(message);
                                $('#error_messages').append(error);
                            });
        
                            $('#error_messages').show();
                        }
                    } else {
                        show_error(810, data.status_message);
                    }
                }
            });
        }
    </script>

</head>
<body>

    <div id="error_messages" class="ui-state-error ui-corner-all" style="display:none;"></div>
    <div id="map_canvas"></div>
    <div id="footer">
        <div id="legend">
            <div id="legend_description"></div>
            <div id="legend_scale">
                <div id="legend_scale_color"></div>
                <span id="legend_scale_text">
                    <span id="legend_scale_text_left"></span>
                    <span id="legend_scale_text_mid"></span>
                    <span id="legend_scale_text_right"></span>
                </span>
            </div>
        </div>
        <div class="footer" id="footerfunctions" style='float:right;'>
            <a href="Javascript:show_flow_details();" title="Show flow details">Flow Lists</a> | 
            <a href="Javascript:show_info('about');" title="Show about information">About</a>
        </div>
    </div>
    
    <div class="panel">
<!-- Zoom Level -->
        <div class="panel_section_title">Zoom</div>
        <div class="panel_section_content">
	    <table>
		<tr>
		    <td style="width:150px">
			<form>
			    <input type="radio" name="zoom_level" id="zoom_level_country" /><label for="zoom_level_country" class="zoom_level_label clickable">Country</label><br />
			    <input type="radio" name="zoom_level" id="zoom_level_region" /><label for="zoom_level_region" class="zoom_level_label clickable">Region</label><br />
		    </td>
		    <td style="width:100px">
			    <input type="radio" name="zoom_level" id="zoom_level_city" /><label for="zoom_level_city" class="zoom_level_label clickable">City</label><br />
			    <input type="radio" name="zoom_level" id="zoom_level_host" /><label for="zoom_level_host" class="zoom_level_label clickable">Host</label><br />
			</form>
		    </td>
		</tr>
	    </table>


        </div>
        <hr />

	<!-- Analysis Type -->
        <div class="panel_section_title">Sources</div>
        <div class="panel_section_content" id="optionPanel">
            <form id="options">
                <table>
                    <tr>
                        <td style="width:90px;">Channels</td>
                        <td>
                            <select id="nfsensources" name="nfsensources[]" multiple="multiple"></select>
                        </td>
                    </tr>
                </table>
                <input type="radio" id="nfsen_option_stattopN" name="nfsen_option" value="1" onclick="if ($('#StartDate').is(':visible')) $('#StartDate').toggle(); if ($('#EndDate').is(':visible')) $('#EndDate').toggle();" />
                <label for="nfsen_option_stattopN" class="clickable">Live TopN</label><br />
                <input type="radio" id="nfsen_option_listflows" name="nfsen_option" value="0" onclick="if (!$('#StartDate').is(':visible')) $('#StartDate').toggle(); if (!$('#EndDate').is(':visible')) $('#EndDate').toggle();" />
                <label for="nfsen_option_listflows" class="clickable">Replay Flows</label><br />

                <div id="StartDate" style="margin-top:10px; width:195px;">
                    <span style="float:left; margin-top:3px;">Start</span>
                    <input type="text" id="date_start" class="date_time_input" />
                    <div class="ui-state-default ui-corner-all no-icon-background" style="float:right; margin-top:2px;">
                        <span class="ui-icon ui-icon-arrowthick-1-e" title="Copy 'end' time to here" onclick="copy_date_time_selector('date_end', 'date_start');"></span>
                    </div>
		<br />
                </div>

                <div id="EndDate" style="margin-top:10px; width:195px;">
                    <span style="float:left; margin-top:3px;">End</span>
                    <input type="text" id="date_end" class="date_time_input" />
                    <div class="ui-state-default ui-corner-all no-icon-background" style="float:right; margin-top:2px;">
                        <span class="ui-icon ui-icon-arrowthick-1-e" title="Copy 'begin' time to here" onclick="copy_date_time_selector('date_start', 'date_end');"></span>
                    </div>
		<br />
                </div>
                
		<hr />

	<!-- Units Setting -->
        <div class="panel_section_title">Units</div>
                <div id="nfsen_stat_order" style="margin-top:10px; margin-bottom:10px; text-align:left;">
                    <input type="radio" name="nfsen_stat_order" value="0" id="nfsen_stat_order_flows" /><label for="nfsen_stat_order_flows">Flows</label>
                    <input type="radio" name="nfsen_stat_order" value="1" id="nfsen_stat_order_packets" /><label for="nfsen_stat_order_packets">Packets</label>
                    <input type="radio" name="nfsen_stat_order" value="2" id="nfsen_stat_order_bytes" /><label for="nfsen_stat_order_bytes">Bytes</label>
                </div>

                <div style="margin-top:10px;">
                    <span style="float:left; margin-top:3px;">Limit to</span>
                    <span style="width:80px; float:right;">
		    <input type="text" id="flow_record_count_input" style="width:35px; padding:2px 0px 2px 0px; text-align:center;" maxlength="5"><label for="flow_record_count_input"> flows</label>
                </div><br />
                <div style="margin-top:10px;">
		    <label for="auto-refresh">Auto-refresh</label>
                    <span style="width:84px; float:right;">
		    <input type="checkbox" id="auto-refresh" />
		</div>
		<!-- Filter -->
		<br />
                <div style="text-align:center; width:195px;">
                    <input type="submit" name="submit" value="Apply" />
                </div>
            </form>

        </div>
    </div>

    <div class="panel_trigger" href="#">Settings</div>
    <div id="netflowDataDetails" style='margin-top: 10px;'></div>
    <div id="error_dialog"></div>
    <div id="warning_dialog"></div>
    <div id="info_dialog"></div>
    <div id="loading_dialog"></div>
    
    <script type="text/javascript">

        $('input#zoom_level_country').click(function () {zoom(0, 2);});
        $('input#zoom_level_region').click(function () {zoom(0, 5);});
        $('input#zoom_level_city').click(function () {zoom(0, 8);});
        $('input#zoom_level_host').click(function () {zoom(0, 10);});
        
	/* Adds missing location information to flow data based on upper layers. */
        function complement_location_information () {
            $.each(flow_data, function (flow_index, flow_item) {
                $.each(zoom_levels, function (zoom_level_index, zoom_level) {                    
                    if (zoom_level_index == 1) { // Region
                        if (flow_item.src_region_lat == undefined) {
                            if (flow_item.src_country_lat == undefined) { 
                                return true;
                            } else { 
                                flow_item.src_region_lat = flow_item.src_country_lat;
                                flow_item.src_region_lng = flow_item.src_country_lng;
                            }
                        }
                        if (flow_item.dst_region_lat == undefined) {
                            if (flow_item.dst_country_lat == undefined) { 
                                return true;
                            } else { 
                                flow_item.dst_region_lat = flow_item.dst_country_lat;
                                flow_item.dst_region_lng = flow_item.dst_country_lng;
                            }
                        }
                    } else if (zoom_level_index == 2) { // City
                        if (flow_item.src_city_lat == undefined) {
                            if (flow_item.src_region_lat != undefined) { 
                                flow_item.src_city_lat = flow_item.src_region_lat;
                                flow_item.src_city_lng = flow_item.src_region_lng;
                            } else if (flow_item.src_country_lat != undefined) { 
                                flow_item.src_city_lat = flow_item.src_country_lat;
                                flow_item.src_city_lng = flow_item.src_country_lng;
                            } else { 
                                return true;
                            }
                        }
                        if (flow_item.dst_city_lat == undefined) {
                            if (flow_item.dst_region_lat != undefined) { 
                                flow_item.dst_city_lat = flow_item.dst_region_lat;
                                flow_item.dst_city_lng = flow_item.dst_region_lng;
                            } else if (flow_item.dst_country_lat != undefined) { 
                                flow_item.dst_city_lat = flow_item.dst_country_lat;
                                flow_item.dst_city_lng = flow_item.dst_country_lng;
                            } else { 
                                return true;
                            }
                        }
                    }
                });
            });
        }
        
        function init_lines () {
            lines = [];

            $.each(flow_data, function (flow_index, flow_item) {
                $.each(zoom_levels, function (zoom_level_index, zoom_level) {
                    if (zoom_level_index == 0) { // Country
                        if (flow_item.src_country_lat == undefined || flow_item.dst_country_lat == undefined)
                            return true;
                    } else if (zoom_level_index == 1) { // Region
                        if (flow_item.src_region_lat == undefined || flow_item.dst_region_lat == undefined)
                            return true;
                    } else if (zoom_level_index == 2) { // City
                        if (flow_item.src_city_lat == undefined || flow_item.dst_city_lat == undefined)
                            return true;
                    }
                    
                    var point1, point2;
                    if (zoom_level_index == 0) { // Country
                        point1 = new google.maps.LatLng(flow_item.src_country_lat, flow_item.src_country_lng);
                        point2 = new google.maps.LatLng(flow_item.dst_country_lat, flow_item.dst_country_lng);
                    } else if (zoom_level_index == 1) { // Region
                        point1 = new google.maps.LatLng(flow_item.src_region_lat, flow_item.src_region_lng);
                        point2 = new google.maps.LatLng(flow_item.dst_region_lat, flow_item.dst_region_lng);
                    } else { // City & Host
                            point1 = new google.maps.LatLng(flow_item.src_city_lat, flow_item.src_city_lng);
                            point2 = new google.maps.LatLng(flow_item.dst_city_lat, flow_item.dst_city_lng);
                    }
                    
                    var lines_index = -1; // -1: line does not exist, >= 0: line index in 'lines' array
                    $.each(lines, function (line_index, line) {
                        if (line.level != zoom_level_index)
                            return true;
                        
                        if ((line.point1.equals(point1) && line.point2.equals(point2)) || (line.point1.equals(point2) && line.point2.equals(point1))) {
                            lines_index = line_index;
                            return false;
                        }
                    });
                    
                    // Create line, if necessary
                    if (lines_index == -1) { 
			// Line does NOT exist
                        var line = {};
                        line.point1 = point1;
                        line.point2 = point2;
                        line.level = parseInt(zoom_level_index, 10);
                        line.entries = [];
                        line.associated_flow_indices = [];
                        lines.push(line);
                        lines_index = lines.length - 1;
                    }
                    
                    // Update flow index association (i.e. index in 'flow_data' array)
                    lines[lines_index].associated_flow_indices.push(flow_index);

                    // Find line entry (if it exists)
                    var entries_index = -1; 
		    // -1: entry does not exist, >= 0: entry index in 'entries' array
                    $.each(lines[lines_index].entries, function (entry_index, entry) {
                        if (zoom_level_index == 0 // Country
                                && entry.src_text.country == flow_item.src_country
                                && entry.dst_text.country == flow_item.dst_country) {
                            entries_index = entry_index;
                            return false;
                        } else if (zoom_level_index == 1 // Region
                                && entry.src_text.country == flow_item.src_country
                                && entry.dst_text.country == flow_item.dst_country
                                && entry.src_text.region == flow_item.src_region
                                && entry.dst_text.region == flow_item.dst_region) {
                            entries_index = entry_index;
                            return false;
                        } else if (zoom_level_index == 2 // City
                                && entry.src_text.country == flow_item.src_country
                                && entry.dst_text.country == flow_item.dst_country
                                && entry.src_text.region == flow_item.src_region
                                && entry.dst_text.region == flow_item.dst_region
                                && entry.src_text.city == flow_item.src_city
                                && entry.dst_text.city == flow_item.dst_city) {
                            entries_index = entry_index;
                            return false;
                        } else if (entry.src_text.country == flow_item.src_country // Host
                                && entry.dst_text.country == flow_item.dst_country
                                && entry.src_text.region == flow_item.src_region
                                && entry.dst_text.region == flow_item.dst_region
                                && entry.src_text.city == flow_item.src_city
                                && entry.dst_text.city == flow_item.dst_city
                                && entry.src_text.ip_address == flow_item.ip_src
                                && entry.dst_text.ip_address == flow_item.ip_dst) {
                            entries_index = entry_index;
                            return false;
                        }
                    });
                    
                    // Create line entry, if necessary. Otherwise, update (existing) line entry.
                    if (entries_index == -1) { 
			// Line entry does NOT exist
                        var line_entry = {};
                        line_entry.packets = flow_item.packets;
                        line_entry.octets = flow_item.octets;
                        line_entry.flows = flow_item.flows;
                        line_entry.duration = flow_item.duration;
                        line_entry.src_text = {};
                        line_entry.dst_text = {};
                        
                        if (zoom_level_index == 0) { // Country
                            line_entry.src_text.country = flow_item.src_country;
                            line_entry.dst_text.country = flow_item.dst_country;
                        } else if (zoom_level_index == 1) { // Region
                            line_entry.src_text.country = flow_item.src_country;
                            line_entry.dst_text.country = flow_item.dst_country;
                            line_entry.src_text.region = flow_item.src_region;
                            line_entry.dst_text.region = flow_item.dst_region;
                        } else if (zoom_level_index == 2) { // City
                            line_entry.src_text.country = flow_item.src_country;
                            line_entry.dst_text.country = flow_item.dst_country;
                            line_entry.src_text.region = flow_item.src_region;
                            line_entry.dst_text.region = flow_item.dst_region;
                            line_entry.src_text.city = flow_item.src_city;
                            line_entry.dst_text.city = flow_item.dst_city;
                        } else { // Host
                            line_entry.src_text.country = flow_item.src_country;
                            line_entry.dst_text.country = flow_item.dst_country;
                            line_entry.src_text.region = flow_item.src_region;
                            line_entry.dst_text.region = flow_item.dst_region;
                            line_entry.src_text.city = flow_item.src_city;
                            line_entry.dst_text.city = flow_item.dst_city;
                            line_entry.src_text.ip_address = flow_item.ip_src;
                            line_entry.dst_text.ip_address = flow_item.ip_dst;
                        }
                        
                        // Add line entry to line
                        lines[lines_index].entries.push(line_entry);
                    } else { 
			// Line entry exists
                        lines[lines_index].entries[entries_index].packets += flow_item.packets;
                        lines[lines_index].entries[entries_index].octets += flow_item.octets;
                        lines[lines_index].entries[entries_index].flows += flow_item.flows;
                        lines[lines_index].entries[entries_index].duration += flow_item.duration;
                    }
                }); 
		// End of zoom_levels
            }); 
	    // End of flow_data
            
            global_line_minima = {
                'country':  -1,
                'region':   -1,
                'city':     -1,
                'host':     -1
            };
            global_line_maxima = {
                'country':  -1,
                'region':   -1,
                'city':     -1,
                'host':     -1
            };
            
            // Determine maxima and sums, both global and per line
            $.each(zoom_levels, function (zoom_level_index, zoom_level) {
                $.each(lines, function (line_index, line) {
                    // Skip line if it doesn't belong to the current zoom level
                    if (line.level != zoom_level_index) {
                        return true;
                    }
                    
                    // Skip line if internal and that traffic should not be considered (setting in config.php)
                    if (config['ignore_marker_internal_traffic_in_line_color_classification'] && line.point1.equals(line.point2)) {
                        return true;
                    }
                    
                    line.flows_sum = 0;
                    line.packets_sum = 0;
                    line.octets_sum = 0;
                    $.each(line.entries, function (entry_index, entry_item) {
                        line.flows_sum += entry_item.flows;
                        line.packets_sum += entry_item.packets;
                        line.octets_sum += entry_item.octets;
                    });
                
                    var line_sum;
                    if (session_data['nfsen_option'] == 0 || session_data['nfsen_stat_order'] == 0) { // Flows
                        line_sum = line.flows_sum;
                    } else if (session_data['nfsen_stat_order'] == 1) { // Packets
                        line_sum = line.packets_sum;
                    } else { // Bytes
                        line_sum = line.octets_sum;
                    }
                    
                    if (global_line_minima[zoom_level] == -1 || global_line_maxima[zoom_level] == -1) { // Initial values
                        global_line_minima[zoom_level] = line_sum;
                        global_line_maxima[zoom_level] = line_sum;
                    } else if (line_sum < global_line_minima[zoom_level]) {
                        global_line_minima[zoom_level] = line_sum;
                    } else if (line_sum > global_line_maxima[zoom_level]) {
                        global_line_maxima[zoom_level] = line_sum;
                    }
                });
            });
            
            // Initialize line objects
            $.each(zoom_levels, function (zoom_level_index, zoom_level) {
                // Check whether global line minima/maxima are very close; if so, this may result in unbalanced color/thinkness ratios
                if (global_line_maxima[zoom_level] - global_line_minima[zoom_level] == 1) {
                    global_line_minima[zoom_level]--;
                    global_line_maxima[zoom_level]++;
                }
                
                // Check whether minimum value has become zero (which is semantically irrealistic)
                if (global_line_minima[zoom_level] == 0) {
                    global_line_minima[zoom_level]++;
                }

                $.each(lines, function (line_index, line) {
                    // Skip line if it doesn't belong to the current zoom level
                    if (line.level != zoom_level_index) {
                        return true;
                    }
                    
                    var line_sum;
                    if (session_data['nfsen_option'] == 0 || session_data['nfsen_stat_order'] == 0) { // Flows
                        line_sum = line.flows_sum;
                    } else if (session_data['nfsen_stat_order'] == 1) { // Packets
                        line_sum = line.packets_sum;
                    } else { // Bytes
                        line_sum = line.octets_sum;
                    }
                    
                    var ratio = (line_sum - global_line_minima[zoom_level]) / (global_line_maxima[zoom_level] - global_line_minima[zoom_level]);
                    if (isNaN(ratio)) {
                        ratio = 0.75;
                    }

                    var color = jQuery.Color({ hue: (1 - ratio) * 120, saturation: 0.7, lightness: 0.5, alpha: 1 }).toHexString();
                    var thickness = Math.max((ratio + 1) * 3, 1.5);
                    var info_window_contents = "<table class=\"flow_info_table\">" + generate_line_info_window_contents(line_index, line.entries) + "</table>";
                    line.obj = create_line (line.point1, line.point2, info_window_contents, color, thickness);
                });
            });
        }

	function init_markers() {

	    markers = [];

            $.each(flow_data, function (flow_index, flow_item) {
                $.each(zoom_levels, function (zoom_level_index, zoom_level) {
                    $.each(['src', 'dst'], function () {
			var check_cnc = false;

			ip_any = flow_item['ip_' + this];
			$.each(cnc_lists, function (cnc_list_index, cnc_ip) {
			    if (cnc_ip == ip_any){
				check_cnc = true;
				return true
			    };
			});

                        var marker_text, entry_text, lat, lng;
                        if (zoom_level_index == 0) { // Country
                            marker_text     = flow_item[this + '_country'];
                            entry_text      = flow_item[this + '_region'];
                            lat             = flow_item[this + '_country_lat'];
                            lng             = flow_item[this + '_country_lng'];
                        } else if (zoom_level_index == 1) {
                            marker_text     = flow_item[this + '_country'] + ", " + flow_item[this + '_region'];
                            entry_text      = flow_item[this + '_city'];
                            lat             = flow_item[this + '_region_lat'];
                            lng             = flow_item[this + '_region_lng'];
                        } else if (zoom_level_index == 2) {
                            marker_text     = flow_item[this + '_country'] + ", " + flow_item[this + '_region'] + ", " + flow_item[this + '_city'];
                            entry_text      = flow_item[this + '_city'];
                            lat             = flow_item[this + '_city_lat'];
                            lng             = flow_item[this + '_city_lng'];
                        } else {
                            marker_text     = flow_item[this + '_country'] + ", " + flow_item[this + '_region'] + ", " + flow_item[this + '_city'];
                            entry_text      = flow_item['ip_' + this];
                            lat             = flow_item[this + '_city_lat'];
                            lng             = flow_item[this + '_city_lng'];
                        }

                        var markers_index = -1; 
                        $.each(markers, function (marker_index, marker) {
                            if (marker.level != zoom_level_index) {
                                return true;
                            }
                            
                            if (marker.point.equals(new google.maps.LatLng(lat, lng))) {
                                markers_index = marker_index;

                                return false;
                            }
                        });
                        
                        if (markers_index == -1) {
                            var marker = {};
                            marker.point = new google.maps.LatLng(lat, lng);
                            marker.level = zoom_level_index;
                            marker.entries = [];
                            marker.associated_flow_indices = [];
                            marker.text = marker_text;
                            markers.push(marker);
                            markers_index = markers.length - 1;
			    marker.extension = (check_cnc) ? 'OTX' : undefined;
                        }

                        markers[markers_index].associated_flow_indices.push(flow_index);
                        
                        var entries_index = -1; 
                        if (markers_index != -1) {
                            $.each(markers[markers_index].entries, function (entry_index, entry) {
                                if (entry.text == entry_text) {
                                    entries_index = entry_index;
                                    return false;
                                }
                            });
                        }
                    
                        if (entries_index == -1) {
                            var marker_entry = {};
                            marker_entry.text = entry_text;
                            
                            if (zoom_level_index == 3) { 
                                marker_entry.flows = flow_item.flows;
                            } else { 
                                marker_entry.hosts = [];
                                var host = (this == 'src') ? flow_item.ip_src : flow_item.ip_dst;
                                if (jQuery.inArray(host, marker_entry.hosts) == -1) { 
                                    marker_entry.hosts.push(host);
                                }
                            }
                            
                            markers[markers_index].entries.push(marker_entry);
                        } else {
                            var host = (this == 'src') ? flow_item.ip_src : flow_item.ip_dst;
                            if (zoom_level_index == 3) { 
                                if (host == markers[markers_index].entries[entries_index].text) {
                                    markers[markers_index].entries[entries_index].flows += flow_item.flows;
                                }
                            } else { 
                                if (jQuery.inArray(host, markers[markers_index].entries[entries_index].hosts) == -1) { 
                                    markers[markers_index].entries[entries_index].hosts.push(host);
                                }
                            }
                        }
                    }); 
                }); 
            }); 
            
            $.each(zoom_levels, function (zoom_level_index, zoom_level) {
                $.each(markers, function (marker_index, marker) {
                    if (marker.level != zoom_level_index) {
                        return true;
                    }
                    
                    var old_entries = marker.entries;
                    marker.entries = [];
                    while (old_entries.length > 0) {
                        var new_entry = old_entries.pop();
                        if (marker.entries.length == 0) {
                            marker.entries.push(new_entry);
                            continue;
                        }
                        
                        var new_comp_value = (zoom_level_index == 3) ? new_entry.flows : new_entry.hosts.length;
                        $.each(marker.entries, function (entry_index, entry) {
                            var comp_value = (zoom_level_index == 3) ? entry.flows : entry.hosts.length;
                            if (new_comp_value >= comp_value) {
                                marker.entries.splice(entry_index, 0, new_entry);
                                return false;
                            }
                            
                            if (entry_index == marker.entries.length - 1) {
                                marker.entries.push(new_entry);
                            }
                        });
                    }
                    
                    var info_window_contents = "<table class=\"flow_info_table\">" + generate_marker_info_window_contents(marker_index, marker.entries) + "</table>";
                    var internal_traffic = false;
                    var cnc_exporter = false;
                    $.each(lines, function (line_index, line) {
                        if (marker.level != zoom_level_index) {
                            return true;
                        }

                        if (line.point1.equals(line.point2)
                                && line.point1.equals(marker.point)) {
                            internal_traffic = true;
                        }

                        if (marker.extension != undefined && marker.extension == 'OTX') {
                            cnc_exporter = true;
			    return true;
                        }
                    });
                    
                    if (internal_traffic) {
                        marker.obj = create_marker (marker.point, format_location_name(marker.text), info_window_contents, 'darkgreen');
                    } else if (cnc_exporter) {
                        marker.obj = create_marker (marker.point, format_location_name(marker.text), info_window_contents, 'cncserver');
                    } else {
                        marker.obj = create_marker (marker.point, format_location_name(marker.text), info_window_contents, 'darkred');
                    }
                });
            });
        }

        function init_legend () {
           
            if (session_data['nfsen_option'] == 0) {
                $('#legend_description').text("Flows:");
            } else {
                switch (session_data['nfsen_stat_order']) {
                    case 0:     $('#legend_description').text("Flows:");
                                break;
                                
                    case 1:     $('#legend_description').text("Packets:");
                                break;
                                
                    case 2:     $('#legend_description').text("Bytes:");
                                break;
                                
                    default:    break;
                }
            }
            
            // Reset legend
            $('#legend_scale_color').empty();
            
            var color;
            for (var i = 120; i >= 0; i = i-1.5) {
                color = jQuery.Color({ hue: i, saturation: 0.7, lightness: 0.5, alpha: 1 }).toHexString();
                $('#legend_scale_color').append("<div style=\"background-color:" + color + "; height:15px; width:10px; display:inline-block; \"></div>");
            }
            
            var zoom_level_name = "";
            switch (get_SM_zoom_level(map.getZoom())) {
                case 0:     zoom_level_name = "country";
                            break;
                            
                case 1:     zoom_level_name = "region";
                            break;
                            
                case 2:     zoom_level_name = "city";
                            break;
                            
                case 3:     zoom_level_name = "host";
                            break;
                            
                default:    break;
            }
            
            var min = global_line_minima[zoom_level_name];
            var max = global_line_maxima[zoom_level_name];
            var mid = (((max + min) / 2) % 1 == 0) ? ((max + min) / 2) : ((max + min) / 2).toFixed(1);
            
            // Hide legend if no lines are visible at all or only one line is visible
            if (min == -1 || max == -1 || min == max) {
                $('#legend #legend_scale_text').css({visibility: 'hidden'});
            } else {
                // Make legend text visible again
                if ($('#legend #legend_scale_text').css('visibility') == 'hidden') {
                    $('#legend #legend_scale_text').css({visibility: ''});
                }
                
                // If 'max < 1000', min is also < 1000 and therefore SI scales will not apply
                if (max < 1000) {
                    min = parseInt(min, 10);
                    mid = parseInt(mid, 10);
                    max = parseInt(max, 10);
                } else {
                    min = apply_SI_Scale(parseInt(min, 10));
                    mid = apply_SI_Scale(parseInt(mid, 10));
                    max = apply_SI_Scale(parseInt(max, 10));
                }
                
                $('#legend_scale_text_left').text(min);
                $('#legend_scale_text_mid').text(mid);
                $('#legend_scale_text_right').text(max);
                
                if (min == mid || max == mid) {
                    $('#legend_scale_text_mid').css({visibility: 'hidden'});
                }
                
                $('#legend_scale_text_mid').css('width', 810 - $('#legend_scale_text_left').width() - $('#legend_scale_text_right').width() - 20);
            }
        }
        
        /*
         * Determines the error message belonging to a certain backend error code.
         */
        function get_backend_error_description (error_code) {
            var description;
            switch (error_code) {
                case 0:     description = "PHP PDO driver for SQLite3 is not installed.";
                            break;
                            
                case 1:     description = "Could not find database file.";
                            break;
                            
                case 2:     description = "The database file is not readable.";
                            break;
                                        
                case 3:     description = "The database file is not writable.";
                            break;
                            
                case 4:     description = "Could not find the geolocation database (MaxMind).";
                            break;
                            
                case 5:     description = "The geolocation database (MaxMind) is not readable.";
                            break;
                            
                case 6:     description = "Could not find the geolocation database (IP2Location).";
                            break;
                
                case 7:     description = "The geolocation database (IP2Location) is not readable.";
                            break;
                            
                default:    description = "Unknown error code (" + error_code + ").";
                            break;
            }
            
            return description;
        }
        
    </script>
</body>
</html>
