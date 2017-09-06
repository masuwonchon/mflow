<?php
#################################################################
#
# mflow.php [MFLOW]
# Author: masuwonchon@gmail.com
#
#################################################################


function MFLOW_ParseInput ($plugin_id) {
	$_SESSION['refresh'] = 0;
}

function MFLOW_Run ($plugin_id) {
    $url = 'plugins/mflow/index.php';
    
	echo "<iframe id='ParentIFrame' src='{$url}' frameborder='0' style='width:100%; height:100%'>Your browser does not support iframes.</iframe>";
}

?>
