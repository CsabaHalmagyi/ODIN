<?php
require_once 'includes/connection.inc.php';
$_SESSION['task']="selectatask";
require_once 'includes/html_top.inc.php';
is_logged_in();
require($_SESSION['settingsfile']);
//unset site settings if there were any
unset($_SESSION['siteprotname']);
unset($_SESSION['siteoid']);
unset($_SESSION['studyprotname']);
unset($_SESSION['studyoid']);


unset($_SESSION['studyParamConf']);
unset($_SESSION['csvdata']);
unset($_SESSION['csvmaxrow']);
unset($_SESSION['csvmaxcol']);
unset($_SESSION['subjectOIDMap']);

unset($_SESSION['syncMap']);
unset($_SESSION['finalSubjects']);
unset($_SESSION['events_server']);
unset($_SESSION['events_client']);

//var_dump($_SESSION);
?>
<br/>

<?php
//check user directory for mapping
if (!file_exists('map/'.htmlspecialchars($_SESSION['user_name']))) {
	//create the user's directory if not exists
	mkdir('map/'.htmlspecialchars($_SESSION['user_name']), 0755, true);
}

//check user directory for xmls
if (!file_exists('savedxmls/'.htmlspecialchars($_SESSION['user_name']))) {
	//create the user's directory if not exists
	mkdir('savedxmls/'.htmlspecialchars($_SESSION['user_name']), 0755, true);
}

//check user directory for temporary files
if (!file_exists('uploads')) {
	//create the user's directory if not exists
	mkdir('uploads', 0755, true);
}


//reset import session if needed
if (isset($_GET['import_session']) && $_GET['import_session']=="reset"){
	$old_importid = $_SESSION['importid'];
	$_SESSION['importid'] = uniqid();
}

?>
<table>
  <thead><tr><td><h2>What would you like to do?</h2></td></tr></thead>
<tbody><tr><td><a href="loadfromcsv.php">Import subjects/schedule events/create and import XML from a CSV file</a></td></tr>
<tr><td><a href="loadfromxml.php">One-way synchronise studies from an ODM XML file</a></td></tr>
<tr><td><a href="">One-way synchronise study rules</a></td></tr>
<tr><td><a href="">Determine the difference between two ODM XML files</a></td></tr>
</tbody>
</table>

<?php 
require_once 'includes/html_bottom.inc.php';
?>