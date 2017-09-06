/******************************
 # dialogs.js [MFLOW]
 # Author: masuwonchon@gmail.com
 #
 *******************************/

function show_warning (code, optional_message) {
    if ($('#loading_dialog').dialog('isOpen')) {
	$('#loading_dialog').dialog('close');
    }
    
    if ($('#warning_dialog').dialog('isOpen')) {
	var new_warning_dialog = {
	    'code':             code,
	    'optional_message': optional_message
	};
	warning_dialog_queue.push(new_warning_dialog);
    } else {
	$('#warning_dialog').empty();
    
	var message = "";
	switch (code) {
	    case 1:     message = "You are running an old version of Microsoft Internet Explorer (< v10). Please note that Mflow has been optimized for Mozilla Firefox, Google Chrome and Apple Safari.";
			break;
	    
	    case 2:     message = "The selected time range appears to contain no flow data. Please adjust the time range.";
			break;
				    
	    case 3:     message = "As a consequence of the applied filters, no flow data could be loaded. Please specify different filters.";
			break;
	
	    default:    message = "An unknown warning occured.";
			break;
	}
	$('#warning_dialog').append("<p><span class=\"ui-icon ui-icon-info\" style=\"float: left; margin: 0 7px 50px 0;\"></span>" + message + "</p>");
    
	if (optional_message != undefined) {
	    $('#warning_dialog').append("<p>Server message: <span style=\"font-style:italic\">" + optional_message + "</span>");
	}
    
	$('#warning_dialog').dialog({
	    buttons: {
		OK: function() {
		    $(this).dialog('close');
		}
	    },
	    close: function (event, ui) {
		if (warning_dialog_queue.length > 0) {
		    var new_warning_dialog = warning_dialog_queue.shift();
		    show_warning(new_warning_dialog.code, new_warning_dialog.optional_message);
		}
	    },
	    closeOnEscape: true,
	    height: 'auto',
	    modal: true,
	    position: 'center',
	    resizable: false,
	    title: 'Warning',
	    width: 'auto'
	}).dialog('open');
	
	// Give the error dialog the highest priority
	$('#error_dialog').dialog('moveToTop');
    }
}

/*
 * Prepares a jQuery error dialog.
 * Parameters:
 *       code - error code
 *       optional_message - optional error message (mostly used for server messages (AJAX/JSON))
 */  
function show_error (code, optional_message) {
    // Close processing message dialog before showing the error
    if ($('#loading_dialog').dialog('isOpen')) {
	$('#loading_dialog').dialog('close');
    }
    
    // If error dialog is already open, queue the new dialog
    if ($('#error_dialog').dialog('isOpen')) {
	var new_error_dialog = {
	    'code':             code,
	    'optional_message': optional_message
	};
	error_dialog_queue.push(new_error_dialog);
    } else {
	$('#error_dialog').empty();
    
	var message = "";
	switch (code) {            
	    // AJAX/JSON communication error codes
	    case 800:   message = "An error occurred while communicating with your Web server. Check your network connectivity and try again.";
			break;
	
	    case 801:   message = "Could not load configuration.";
			break;
		    
	    case 802:   message = "Could not load NfSen configuration.";
			break;
		    
	    case 803:   message = "Could not load session data.";
			break;
	
	    case 804:   message = "Could not retrieve flow data.";
			break;
				
	    case 805:   message = "Could not retrieve geolocation data.";
			break;
		    
	    case 806:   message = "Could not retrieve geocoder data.";
			break;
		    
	    case 807:   message = "Could not store session data.";
			break;
		    
	    case 808:   message = "Could not retrieve geocoder data (server).";
			break;
		    
	    case 809:   message = "Could not store geocoder data.";
			break;
		    
	    case 810:   message = "Could not retrieve backend status.";
			break;
		    
	    case 811:   message = "Could not retrieve extensions.";
			break;
		    
	    case 812:   message = "Could not retrieve last used version number.";
			break;
		    
	    case 813:   message = "Could not load configuration (constants).";
			break;
		    
	    case 814:   message = "Could not apply geo filter.";
			break;
		    
	    case 815:   message = "Could not resolve hostname(s).";
			break;
		    
	    case 816:   message = "Could not write to syslog.";
			break;
			
	    case 817:   message = "Could not store misc value(s).";
			break;
	
	    // Client-side-only error codes
	    case 996:   message = "You have specified an invalid map center. Please check your configuration.";
			break;
			
	    case 997:   message = "You have selected an invalid time range. The end time should come after the begin time.";
			break;
	
	    case 998:   message = "You have limited the number of selected flow records to 0. Please enter a number of flow records > 0.";
			break;
		    
	    case 999:   message = "You have to select at least one source to continue.";
			break;
		    
	    default:    message = "An unknown error occured.";
			break;
	}
    
	$('#error_dialog').append("<p><span class=\"ui-icon ui-icon-alert\" style=\"float: left; margin: 0 7px 50px 0;\"></span>" + message + "</p>");
    
	if (optional_message != undefined) {
	    $('#error_dialog').append("<p>Server message: <span style=\"font-style:italic\">" + optional_message + "</span>");
	}
    
	$('#error_dialog').dialog({
	    buttons: {
		OK: function() {
		    $(this).dialog('close');
		}
	    },
	    close: function (event, ui) {
		if (error_dialog_queue.length > 0) {
		    var new_error_dialog = error_dialog_queue.shift();
		    show_error(new_error_dialog.code, new_error_dialog.optional_message);
		}
	    },
	    closeOnEscape: true,
	    height: 'auto',
	    modal: false,
	    position: 'center',
	    resizable: false,
	    title: 'Error',
	    width: 'auto'
	}).dialog('open');
	
	// Give the error dialog the highest priority
	$('#error_dialog').dialog('moveToTop');
    }
}

function show_info (type) {
    var text;
    $('#info_dialog').empty();
    
    if (type == 'about') {
	text = 'Mflow has been developed by:<br /><br /> Suwon.CHON (masuwonchon@gmail.com)<br /> Juwon.BANG (juwon1405@hotmail.com)<br /> \
		<br /><hr />';
	$('#info_dialog').append(text);
	
	var footer = $('<div />', { 'id': 'info_dialog_footer' });
	
	var logo = $('<img />').css({
	    'float':    'right',
	    'width':    '90px'
	});

	logo.attr('src', 'img/mflow.png').attr('alt', 'mflow');

	footer.append(logo);
	
	$('#info_dialog').append(footer);
	$('#info_dialog').dialog({
	    closeOnEscape: true,
	    height: 'auto',
	    modal: true,
	    position: 'center',
	    resizable: false,
	    title: 'Info',
	    width: 400
	}).dialog('open');
	
	// Give the error dialog the highest priority
	$('#error_dialog').dialog('moveToTop');

    } else if (type == 'help') {
	$('#info_dialog').html('Welcome to the Mflow help. Some main principles of Mflow are explained here.<br /><br /> \
		<table id=\"help\"> \
		    <tr> \
			<td class=\"help_item\">Marker</td> \
			<td>Markers represent hosts and show information about them, such as IPv4 addresses and the country, region and city they\'re located in. A green marker indicates the presence of a flow of which the source and destination are located \'inside\' the same marker.<hr /></td> \
		    </tr> \
		    <tr> \
			<td class=\"help_item\">Line</td> \
			<td>Lines represent a flow between two hosts (so between markers) and show information about that flow, like the geographical information of the two end points, the exchanged amount of packets, octets and throughput per flow.<hr /></td> \
		    </tr> \
		    <tr> \
			<td class=\"help_item\">Zoom levels table</td> \
			<td>This tables shows the current zoom level. The four zoom levels are also clickable, so that you can zoom in or out to a particular zoom level directly.<hr /></td> \
		    </tr> \
		    <tr> \
			<td class=\"help_item\">NfSen options</td> \
			<td>The main NfSen options - <i>List Flows</i> or <i>Stat TopN</i> - can be set here. The first option lists the first N flows of the selected time slot (N and the selected time slot will be discussed later). <i>Stat TopN</i> shows top N statistics about the network data in the selected time slot. The value of N can be set in the <i>Limit to</i> field, while the time slot can be set in the <i>Begin</i> and <i>End</i> fields.</td> \
		    </tr> \
		</table>');
	$('#info_dialog').dialog({
	    closeOnEscape: true,
	    height: 'auto',
	    modal: true,
	    position: 'center',
	    resizable: false,
	    title: 'Help',
	    width: '500px'
	}).dialog('open');
	
	// Give the error dialog the highest priority
	$('#error_dialog').dialog('moveToTop');

    }
}

/*
 * Shows a loading message.
 *
 * Parameters:
 *       text - Text shown as part of the loading message (optional)
 */ 
function show_loading_message (text) {
    if (!$('#loading_dialog').dialog('isOpen')) {
	$('#loading_dialog').html("<div id='processing' style='text-align:center; clear:both;'> \
		<img src='img/load.gif' alt='Loading Mflow'><br /> \
		<div style='font-size:8pt; margin-top:15px;'> \
		<p id='loading_text_upper'>Loading...</p> \
		<p id='loading_text_lower'></p> \
		<p id='loading_text_long' style='display:none;'>Your request is still being \
			    processed by the server. Please don't refresh this page.</p> \
		</div> \
	    </div>");
	
	$('#loading_dialog').dialog({
	    closeOnEscape: false,
	    dialogClass: 'dialog_no_title',
	    modal: true,
	    position: 'center',
	    resizable: false,
	    width: 300,
	}).dialog('open');
	
	// Give the error dialog the highest priority
	$('#error_dialog').dialog('moveToTop');
	
	// loading_message_timeout_handle has been declared in index.php
	loading_message_timeout_handle = setInterval(
	    function () {
		if (ajax_error) {
		    clearInterval(loading_message_timeout_handle);
		    $('#loading_dialog').dialog('close');
		    return;
		}
		if ($('#loading_dialog').dialog('isOpen')) {
		    
		    // Check whether message for long duration is visible (and therefore p#loading_text_lower is not)
		    if ($('#loading_dialog p#loading_text_long').css('display') == "none") {
			$('#loading_dialog p#loading_text_long').show();
			$('#loading_dialog p#loading_text_upper').hide();
			$('#loading_dialog p#loading_text_lower').hide();
		    } else {
			$('#loading_dialog p#loading_text_long').hide();
			$('#loading_dialog p#loading_text_upper').show();
			$('#loading_dialog p#loading_text_lower').show();
		    }
		}
	    }, 10000);
    }
    
    if (text === '' || text == undefined) {
	$('#loading_dialog').dialog('option', 'height', 90);
	$('#loading_text_lower').hide();
    } else {
	if (text.charAt(text.length - 1) != ".") {
	    text += "...";
	}
	$('#loading_text_lower').text(text);
	
	if (!$('#loading_text_lower').is('visible')) {
	    $('#loading_dialog').dialog('option', 'height', 115);
	    $('#loading_text_lower').show();
	}
	
	// If the loading text is changed, make sure that the message for long duration is hidden
	if (!($('#loading_dialog p#loading_text_long').css('display') == "none")) {
	    $('#loading_dialog p#loading_text_long').hide();
	    $('#loading_dialog p#loading_text_upper').show();
	    $('#loading_dialog p#loading_text_lower').show();
	}
    }
}

/*
 * Generates a dialog showing 'Flow Details'.
 * 
 * Parameters:
 *      flow_indices - Indices of flow data in 'flow_data' array of which the flow data needs to be shown.
 */
function show_flow_details (flow_indices) {
    var field_names = [];
    field_names['start_time']   = 'Start time';
    field_names['duration']     = 'Duration';
    field_names['ip_src']       = 'Src. address';
    field_names['ip_dst']       = 'Dst. address';
    field_names['port_src']     = 'Src. port';
    field_names['port_dst']     = 'Dst. port';
    field_names['protocol']     = 'Protocol';
    field_names['packets']      = 'Packets';
    field_names['octets']       = 'Octets';
    field_names['flows']        = 'Flows';
    
    var static_field_count;
    if (flow_indices == undefined) { // General 'Flow Details'
	field_names['location_src'] = 'Source location';
	field_names['location_dst'] = 'Destination location';
	static_field_count = 11;
    } else { // 'Flow Details' from information window
	static_field_count = 9;
    }
    
    var field_count = static_field_count + extensions.length;
    
    // Add extension field names to list of fields
    $.each(extensions, function (extension_ID, extension) {
	$.each(this.fields, function (field_ID, field) {
	    // Dollar-sign should be removed from 'nfdump_short' name
	    field_names[field.nfdump_short.substr(1)] = field.short_name;
	});
    });
    
    protocols = [];
    protocols[1] = 'ICMP';
    protocols[2] = 'IGMP';
    protocols[6] = 'TCP';
    protocols[8] = 'EGP';
    protocols[9] = 'IGP';
    protocols[17] = 'UDP';
    protocols[41] = '6in4';
    protocols[46] = 'RSVP';
    protocols[47] = 'GRE';
    protocols[50] = 'ESP';
    protocols[51] = 'AH';
    protocols[58] = 'ICMPv6';
    
    // Generate header line
    var body = $('<tbody/>');
    var header_line = $('<tr/>', {'class': 'header'});
    var key_index = 0;
    for (var key in field_names) {
	var element = $('<th/>').text(field_names[key]);
	
	if (key_index == 0) { // First field
	    element.addClass('left');
	} else if (key_index == field_count) { // Last field
	    element.addClass('right');
	}
	
	if (key == 'ip_src') {
	    element.addClass('src_column');
	} else if (key == 'ip_dst') {
	    element.addClass('dst_column');
	}
	
	header_line.append(element);
	key_index++;
    }
    body.append(header_line);
    
    var line_class = 'odd';
    if (flow_data != undefined) {
	$.each(flow_data, function (flow_index, flow_item) {
	    // Skip flow record if it doesn't belong to the current information window
	    if (flow_indices != undefined && jQuery.inArray(flow_index, flow_indices) == -1) {
		return true;
	    }
	
	    var body_line = $('<tr/>', {'class': line_class});
	
	    for (var key in field_names) {
		var field = $('<td/>');
	    
		if (key == 'ip_src') {
		    field.addClass('src_column');
		} else if (key == 'ip_dst') {
		    field.addClass('dst_column');
		}
	    
		if (key == 'protocol') {
		    // Replace protocol number by protocol name, if known
		    if (protocols[flow_item[key]] != undefined) {
			field.text(protocols[flow_item[key]]);
		    } else {
			field.text(flow_item[key]);
		    }
		} else if (key == 'location_src') {
		    var location_string = format_location_name(flow_item['src_country']);
		    
		    if (flow_item['src_region'] != "(UNKNOWN)") {
			location_string += ", " + format_location_name(flow_item['src_region']);
		    }
	    
		    if (flow_item['src_city'] != "(UNKNOWN)") {
			location_string += ", " + format_location_name(flow_item['src_city']);
		    }
		
		    field.text(location_string).css('padding-right', '5px');
		} else if (key == 'location_dst') {
		    var location_string = format_location_name(flow_item['dst_country']);
		
		    if (flow_item['dst_region'] != "(UNKNOWN)") {
			location_string += ", " + format_location_name(flow_item['dst_region']);
		    }
	    
		    if (flow_item['dst_city'] != "(UNKNOWN)") {
			location_string += ", " + format_location_name(flow_item['dst_city']);
		    }
		
		    field.text(location_string).css('padding-left', '5px');
		} else if (key == 'packets' || key == 'octets') {
		    field.text(apply_SI_Scale(flow_item[key]));
		} else if (key == 'start_time') {
		    field.text(flow_item[key].substring(0, flow_item[key].lastIndexOf('.') + 2));
		} else if (key == 'duration') {
		    field.text(flow_item[key].toFixed(1));
		} else {
		    field.text(flow_item[key]);
		}
	    
		body_line.append(field);
	    }
	
	    var line_fields = body_line.children('td');
	
	    if (flow_indices == undefined
		    && $(line_fields[static_field_count - 2]).text() == $(line_fields[static_field_count - 1]).text()) {
		$(line_fields[static_field_count - 2]).attr('colspan', '2');
		$(line_fields[static_field_count - 1]).remove();
	    }
	
	    body.append(body_line);
	    line_class = (line_class == 'odd') ? 'even' : 'odd';
	});
    }
    
    $('#info_dialog').html("<table class=\"flow_info_table\">" + body.html() + "</table>");
    $('#info_dialog').dialog({
	closeOnEscape: true,
	height: 'auto',
	maxHeight: $('#map_canvas').height() - 60,
	modal: false,
	position: { my: 'center top', at: 'center top+30px', of: $('#map_canvas') },
	resizable: true,
	title: 'Flow details',
	width: 800,
	show : {
	    effect: "blind",
	    duration: 500
	},
	hide : {
	    effect: "clip",
	    duration: 1000
	},
    }).dialog('open');
    
    $('#error_dialog').dialog('moveToTop');
    
    if (resolved_hostnames != undefined) {
	$.each(resolved_hostnames, function (index, tuple) {
	    var ip_address = $('#info_dialog .flow_info_table td:contains(' + tuple.address + ')');
	
	    // If the IP address is present, add its hostname
	    if (ip_address.length > 0) {
		ip_address.attr('title', tuple.hostname);
	    }
	});
    }
}
