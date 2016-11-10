<?php
header ( "Content-Type: text/html; charset=utf-8" );

if ( $_SESSION['user_id'] ) {
require($_SESSION['settingsfile']);}

$progressDefinitions = array(
	"selectatask"=>array(
	"phases"=>array("Select a task"),
	"files"=>array("index.php"=>0)				
	),	
	"loadfromcsv"=>array(
	"phases"=>array("Select a study", "Upload data file", "Validate data file", "Import subjects", "Schedule events",
					"Upload mapping file", "Define CRF versions", "Mapping", "Create XML"),
	"files"=>array(	"loadfromcsv.php"=>0,
					"upload.php"=>1,
					"validation.php"=>2,
					"importsubjects.php"=>3,
					"schedule.php"=>4,
					"crfdef.php"=>6,
					"uploadmap.php"=>5,
					"map.php"=>7,
					"createxml.php"=>8,
					"importxml.php"=>9,
					"profile.php"=>-1,
					"templates.php"=>-1,
					"index.php"=>-1			
	)					
	),	
	"loadfromxml"=>array(
	"phases"=>array("Select study and upload XML", "Determine sync map", "Import subjects from XML", "Synchronise events", "Create data XML"),
	"files"=>array("loadfromxml.php"=>0,
					"uploadxml.php"=>0,
					"syncmap.php"=>1,
					"syncsubjects.php"=>2,
					"syncevents.php"=>3,
					"synccreatexml.php"=>4
			
	)				
	)			
);


$task = $_SESSION['task'];
if ($task == "loadfromcsv" && isset($allowImport) && $allowImport){
	$progressDefinitions[1]["phases"][]="Import";
}


?>

<!DOCTYPE html>
<html>

<head>
<link rel="stylesheet" href="css/style.css" type="text/css" />
<link rel="stylesheet" href="css/icon.css" type="text/css" />
<link rel="stylesheet" type="text/css" href="css/easyui.css"/>
<title>O D I N</title>
<script
	src="js/jquery.min.js"></script>
<script type="text/javascript"
	src="js/jquery.easyui.min.js"></script>
</head>

<body>

		<div id="wrapper">
			<div id="headerwrap">
				<div id="header">
					<p><span id="odin_capital">O</span><span id="odin_normal">penClinica </span><span id="odin_capital">D</span><span id="odin_normal">ata </span><span id="odin_capital">I</span><span id="odin_normal">mporter web applicatio</span><span id="odin_capital">N</span></p>
				</div>
			</div>
			<div id="navigationwrap">
				<div id="navigation">
				

					<?php

 					if ( $_SESSION['user_id'] ) {
					echo '<a href="index.php?import_session=reset" class="menus">Home</a>';
					echo '<a href="profile.php" class="menus">Profile</a>';
					echo '<a href="templates.php" class="menus">Templates</a>';
						
 					echo '<span id="menu_user_name">Logged in as: <a href="profile.php"><b>'.htmlspecialchars($_SESSION['user_name'])." ".'</b></a> <a href="logout.php">(Logout)</a></span></p>';
					}
					else {
						echo '<span id="menu_user_name">Please log in!</span>';
					}        
?>
					
					

				</div>
			</div>
			<div id="contentwrap">
			<?php if ( $_SESSION['user_id'] ) {?>
			<div id="progressbar"><small><?php 

			$boldIndex = -1;
			if (isset($progressDefinitions[$task]["files"][basename($_SERVER['PHP_SELF'])])){
				$boldIndex = $progressDefinitions[$task]["files"][basename($_SERVER['PHP_SELF'])];
			}
				
			for($i=0;$i<sizeof($progressDefinitions[$task]["phases"]);$i++){
				if ($i==$boldIndex) {echo '<b>'.$progressDefinitions[$task]["phases"][$i].'</b>';}
				else echo $progressDefinitions[$task]["phases"][$i];
				
				if($i+1<sizeof($progressDefinitions[$task]["phases"])){
					echo ' > ';
				}
			}


			?></small></div>
			
			<?php }?>
				<div id="content">

				<div id="sessiondata">
				<p>
				<?php 

				//var_dump($_SESSION);
				
				if ($_SESSION['user_id']){
				?>

				<?php
				}
				?>
				</p>
				</div>
