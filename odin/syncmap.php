<?php

require_once 'includes/connection.inc.php';
require_once 'includes/html_top.inc.php';
require_once 'classes/OpenClinicaSoapWebService.php';
require_once 'classes/OpenClinicaODMFunctions.php';

is_logged_in();
require($_SESSION['settingsfile']);
//add the session id to the datafile's name


//reading client xml data

$odmMeta_client = simplexml_load_file("uploads/".$_SESSION['xmlFile']);
//$odmMeta_client = simplexml_load_string($odmMetaRaw_client[0]);
$odmMeta_client->registerXPathNamespace('odm', OpenClinicaSoapWebService::NS_ODM);

$studyOID_client = $odmMeta_client->Study->attributes()->OID;
$studyName_client = $odmMeta_client->Study->GlobalVariables->ProtocolName;

$events_client = array();
$forms_client = array();
$groups_client = array();
$items_client = array();

//client events
foreach ($odmMeta_client->Study->MetaDataVersion->StudyEventDef as $eventDefs){
	$eventId = (string)$eventDefs->attributes()->OID;
	$eventName = (string)$eventDefs->attributes()->Name;
	$refs = array();
	$eventRepeating = (string)$eventDefs->attributes()->Repeating;
	foreach ($eventDefs->FormRef as $formRefs){
		$formRef = (string)$formRefs->attributes()->FormOID;
		$refs[] = $formRef;
	}
	$events_client[$eventId]=array("name"=>$eventName,"repeating"=>$eventRepeating, "refs"=>$refs);
}

//client forms
foreach ($odmMeta_client->Study->MetaDataVersion->FormDef as $formDefs){
	$formId = (string)$formDefs->attributes()->OID;
	$formName = (string)$formDefs->attributes()->Name;
	$refs = array();
	foreach ($formDefs->ItemGroupRef as $igRefs){
		$igRef = (string)$igRefs->attributes()->ItemGroupOID;
		$refs[] = $igRef;
	}
	$forms_client[$formId]= array ("name"=>$formName,"refs"=>$refs);
}

//client groups
foreach ($odmMeta_client->Study->MetaDataVersion->ItemGroupDef as $igDefs){
	$igId = (string)$igDefs->attributes()->OID;
	$igName = (string)$igDefs->attributes()->Name;
	$refs = array();
	foreach ($igDefs->ItemRef as $iRefs){
		$iRef = (string)$iRefs->attributes()->ItemOID;
		$refs[] = $iRef;
	}
	$groups_client[$igId]= array ("name"=>$igName,"refs"=>$refs);
}

//client items
foreach ($odmMeta_client->Study->MetaDataVersion->ItemDef as $iDefs){
	$iId = (string)$iDefs->attributes()->OID;
	$iName = (string)$iDefs->attributes()->Name;
	$namespaces = $iDefs->getNameSpaces(true);
	$OpenClinica = $iDefs->children($namespaces['OpenClinica']);
	$fOID = array();
	foreach ($OpenClinica as $oc){
		$subelement = $oc->children($namespaces['OpenClinica']);
		foreach ($subelement as $sube){
			$subattr = $sube->attributes();
			$fOID[] = (string)$subattr['FormOID'];
		}
	}

	$items_client[$iId]= array ("name"=>$iName,"foid"=>$fOID);
}

$ocUniqueProtocolId_server = $_SESSION['studyprotname'];

$meta_server = 	new OpenClinicaSoapWebService($ocWsInstanceURL, $_SESSION ['user_name'], $_SESSION ['passwd']);

// get metadata from server
$getMetadata = $meta_server->studyGetMetadata ( $ocUniqueProtocolId_server );

$odmMetaRaw_server = $getMetadata->xpath('//v1:odm');

$odmMeta_server = simplexml_load_string($odmMetaRaw_server[0]);

//$odmMeta_server = simplexml_load_file('uploads/mothership_sitessubj.xml');
$odmMeta_server->registerXPathNamespace('odm', OpenClinicaSoapWebService::NS_ODM);

$studyOID_server = $odmMeta_server->Study->attributes()->OID;
$studyName_server = $odmMeta_server->Study->GlobalVariables->StudyName;

$events_server = array();
$forms_server = array();
$groups_server = array();
$items_server = array();

//server events
foreach ($odmMeta_server->Study->MetaDataVersion->StudyEventDef as $eventDefs){
	$eventId = (string)$eventDefs->attributes()->OID;
	$eventName = (string)$eventDefs->attributes()->Name;
	$refs = array();
	$eventRepeating = (string)$eventDefs->attributes()->Repeating;
	foreach ($eventDefs->FormRef as $formRefs){
		$formRef = (string)$formRefs->attributes()->FormOID;
		$refs[] = $formRef;
	}
	$events_server[$eventId]=array("name"=>$eventName,"repeating"=>$eventRepeating, "refs"=>$refs);
}

//server forms
foreach ($odmMeta_server->Study->MetaDataVersion->FormDef as $formDefs){
	$formId = (string)$formDefs->attributes()->OID;
	$formName = (string)$formDefs->attributes()->Name;
	$refs = array();
	foreach ($formDefs->ItemGroupRef as $igRefs){
		$igRef = (string)$igRefs->attributes()->ItemGroupOID;
		$refs[] = $igRef;
	}
	$forms_server[$formId]= array ("name"=>$formName,"refs"=>$refs);
}

//server groups
foreach ($odmMeta_server->Study->MetaDataVersion->ItemGroupDef as $igDefs){
	$igId = (string)$igDefs->attributes()->OID;
	$igName = (string)$igDefs->attributes()->Name;
	$refs = array();
	foreach ($igDefs->ItemRef as $iRefs){
		$iRef = (string)$iRefs->attributes()->ItemOID;
		$refs[] = $iRef;
	}
	$groups_server[$igId]= array ("name"=>$igName,"refs"=>$refs);
}

//server items
foreach ($odmMeta_server->Study->MetaDataVersion->ItemDef as $iDefs){
	$iId = (string)$iDefs->attributes()->OID;
	$iName = (string)$iDefs->attributes()->Name;
	$namespaces = $iDefs->getNameSpaces(true);
	$OpenClinica = $iDefs->children($namespaces['OpenClinica']);
	$fOID = array();
	foreach ($OpenClinica as $oc){
		$subelement = $oc->children($namespaces['OpenClinica']);
		foreach ($subelement as $sube){
			$subattr = $sube->attributes();
			$fOID[] = (string)$subattr['FormOID'];
		}
	}

	$items_server[$iId]= array ("name"=>$iName,"foid"=>$fOID);
}

$syncMap = array();

foreach ($events_client as $ekey=>$ev){
	$formR = $ev['refs'];
	foreach ($formR as $frkey){
		$igRef = $forms_client[$frkey]['refs'];
		foreach ($igRef as $igkey){
			$irefs = $groups_client[$igkey]['refs'];
			//display all the items associated with the current form version
			foreach ($irefs as $ikey){
				$mapID = $events_client[$ekey]['name'].'::'.$forms_client[$frkey]['name'].'::'.$items_client[$ikey]['name'];
				$clientOIDComp = $ekey.'::'.$frkey.'::'.$igkey.'::'.$ikey;
				$syncMap[$mapID]=array('client'=>$clientOIDComp,'server'=>null);
			}
		}
	}
}



foreach ($events_server as $ekey=>$ev){
	$formR = $ev['refs'];
	foreach ($formR as $frkey){
		$igRef = $forms_server[$frkey]['refs'];
		foreach ($igRef as $igkey){
			$irefs = $groups_server[$igkey]['refs'];
			//display all the items associated with the current form version
			foreach ($irefs as $ikey){
				//$mapID = $events_server[$ekey]['name'].'::'.$forms_server[$firstFR]['name'].'::'.$groups_server[$igr]['name'].'::'.$items_server[$item]['name'];
				$mapID = $events_server[$ekey]['name'].'::'.$forms_server[$frkey]['name'].'::'.$items_server[$ikey]['name'];
				$serverOIDComp = $ekey.'::'.$frkey.'::'.$igkey.'::'.$ikey;
				if(isset($syncMap[$mapID])){
					$syncMap[$mapID]['server']=$serverOIDComp;
				}
			}
		}
	}
}


$_SESSION['syncMap']=$syncMap;
$_SESSION['events_server']=$events_server;
$_SESSION['events_client']=$events_client;

$syncStudies = array();
$isSite = false;
foreach ($odmMeta_client->Study as $studyDefs){
	$studyOID = (string)$studyDefs->attributes()->OID;
	$studyName = (string)$studyDefs->GlobalVariables->ProtocolName;
	$syncStudies[$studyName]=array("name"=>$studyName, "clientoid"=>$studyOID, "issite"=>$isSite);
	$isSite=true;
}

foreach ($odmMeta_server->Study as $studyDefs){
	$studyOID = (string)$studyDefs->attributes()->OID;
	$studyName = (string)$studyDefs->GlobalVariables->ProtocolName;
	if (isset($syncStudies[$studyName])){
		$syncStudies[$studyName]["serveroid"]=$studyOID;
	}
	
}
$_SESSION['syncStudies']=$syncStudies;


?>

<table name="syncStudy"><thead><tr><td>XML Study - Site</td><td>Server equivalent</td></tr></thead>
<tbody>
<?php 



$missingSites = 0;
$matchedSites = 0;

foreach($syncStudies as $sskey=>$ss){
	echo "<tr><td>";
	echo $ss['name'];
	echo "</td><td>";
	
	if ($ss['serveroid'] == null){
		echo '<span class="error">Study/site cannot be found.</span>';
		$missingSites++;
	}
	else {
		echo '<span class="success">Study/site found.</span>';
		$matchedSites++;
	}
	echo "</td></tr>";
}

?>
</tbody>
</table>

<?php 
echo "<p>Matched Study/sites: ".$matchedSites."<br/>";

echo "Unmatched Study/sites: ".$missingSites;
echo "</p>";

?>


<table name="syncMap"><thead><tr><td>XML Event - Form</td><td>Server equivalent</td></tr></thead>
<tbody>
<?php 

$evformMap = array();
foreach($syncMap as $smrefkey=>$smref){
	$eventform = explode("::",$smrefkey);
	$evform = $eventform[0]." - ".$eventform[1];
	
	if($smref['server'] == null || $smref['server'] == ""){
	$evformMap[$evform] = null;		
	}
	else{
		$evformMap[$evform]="ok";
	}
}

$missingCrfs = 0;
$matchedCrfs = 0;

foreach($evformMap as $efmapkey=>$efmap){
	echo "<tr><td>";
	echo $efmapkey;
	echo "</td><td>";
	
	if ($efmap == null){
		echo '<span class="error">CRF cannot be found.</span>';
		$missingCrfs++;
	}
	else {
		echo '<span class="success">CRF matched.</span>';
		$matchedCrfs++;
	}
	echo "</td></tr>";
}

?>
</tbody>
</table>
<?php 
echo "<p>Matched CRFs: ".$matchedCrfs."<br/>";

echo "Unmatched CRFs: ".$missingCrfs;
echo "<br/><br/>";
echo '<a href="index.php" class="easyui-linkbutton" data-options="iconCls:\'icon-back\'">Go back</a> OR <a href="syncsubjects.php" class="easyui-linkbutton" data-options="iconCls:\'icon-next\'">Continue to import subjects</a></p>';
?>



<?php 
require_once 'includes/html_bottom.inc.php';
?> 