<?php

require_once 'includes/connection.inc.php';
require_once 'includes/html_top.inc.php';
require_once 'classes/OpenClinicaSoapWebService.php';
require_once 'classes/OpenClinicaODMFunctions.php';

is_logged_in();
require($_SESSION['settingsfile']);
//add the session id to the datafile's name


$meta_server = 	new OpenClinicaSoapWebService($ocWsInstanceURL, $_SESSION ['user_name'], $_SESSION ['passwd']);

$odmMeta_client = simplexml_load_file("uploads/".$_SESSION['xmlFile']);
//$odmMeta_client = simplexml_load_string($odmMetaRaw_client[0]);
$odmMeta_client->registerXPathNamespace('odm', OpenClinicaSoapWebService::NS_ODM);


$finalSubjects = $_SESSION['finalSubjects'];
$events_server = $_SESSION['events_server'];
$events_client = $_SESSION['events_client'];
$syncStudies = $_SESSION['syncStudies'];

$syncMap = $_SESSION['syncMap'];
?>

<?php

$finalXMLs = array();

//creating the ODM XML
foreach ($odmMeta_client->ClinicalData as $clientClinicalDataNode){
	
	$siteID_server = null;
	$study = null;
	$studyOID_client = (string)$clientClinicalDataNode->attributes()->StudyOID;
	foreach($_SESSION['syncStudies'] as $sskey=>$ss){
		if($ss['clientoid'] == $studyOID_client){
			$study = $ss['serveroid'];
			$studyName = $sskey;
		}
	}
	
		
	if(!empty($study)){
		$odmXML = new ocODMclinicalData($study, 1, array());
		
		
		$rowCounter=1;
		foreach ($clientClinicalDataNode->SubjectData as $clientSubjectDataNode){
			$subjectID = (string)$clientSubjectDataNode->attributes('OpenClinica',TRUE)->StudySubjectID;
			$subjectOID_final = '';
		
			foreach($finalSubjects as $subkey=>$subval){
		
				if($subval['subjID']==$subjectID){
					$subjectOID_final = $subval['serverSubjOID'];
					break;
				}
			}
		
			if ($subjectOID_final == '' || $subjectOID_final == null) continue;
		
			foreach($clientSubjectDataNode->StudyEventData as $clientEventNode){
				$clientEventOID = (string)$clientEventNode->attributes()->StudyEventOID;
				$eventOccurrence = $clientEventNode->attributes()->StudyEventRepeatKey;
				$serverEventOID='';

				if (empty($eventOccurrence)) {
					$eventOccurrence = 0;
				}

				//preparing the ODM XML structure
		
		
				foreach($clientEventNode->FormData as $formdata){
					$formOID_client = (string)$formdata->attributes()->FormOID;
					$formstatus = (string)$formdata->attributes('OpenClinica',TRUE)->Status;
		
					foreach($formdata->ItemGroupData as $itemgroupdata){
						$itemgroupOID_client = (string)$itemgroupdata->attributes()->ItemGroupOID;
						$itemgroupRepeatKey_client = $itemgroupdata->attributes()->ItemGroupRepeatKey;

						if (empty($itemgroupRepeatKey_client)) $itemgroupRepeatKey_client = 1;
						
						foreach($itemgroupdata->ItemData as $itemdata){
							$itemOID_client = $itemdata->attributes()->ItemOID;
							$itemvalue = (string)$itemdata->attributes()->Value;
		
							$clientOIDComposition = $clientEventOID."::".$formOID_client."::".$itemgroupOID_client."::".$itemOID_client;
							//echo "<td>".$clientOIDComposition."</td>";
		
							$serverOIDComposition = '';
							
 							foreach($syncMap as $sm=>$smval){
								if($smval['client']==$clientOIDComposition){
									$serverOIDComposition = $smval['server'];
									break;
								}
							}
							
							//echo "<td>".$serverOIDComposition."</td></tr>";
							
							if(!empty($serverOIDComposition)){
								$meta=explode("::",$serverOIDComposition);
									
								if ($eventOccurrence==0){
									$eventOccurrenceMod=1;
								}
								else{
									$eventOccurrenceMod=$eventOccurrence;
								}
		
								//echo $study." ".$subjectOID_final." ". $meta[0]." ". $eventOccurrenceMod." ". $meta[1]." ". $meta[2]." ".$itemgroupRepeatKey_client." ". $meta[3]." ".$itemvalue."<br/>";
								if(!empty($study) && !empty($subjectOID_final) && !empty($meta[0]) && !empty($meta[1]) && !empty($meta[2]) && !empty($meta[3]) && !empty($itemvalue) ){
									$odmXML->add_subject($subjectOID_final, $meta[0], $eventOccurrenceMod, $meta[1], $formstatus, $meta[2],$itemgroupRepeatKey_client, $meta[3], $itemvalue);
									
								}
							}
						}
					}
				}
			}
		}
		
		
		
		//create the xml file for the study
		$xml = ocODMtoXML(array($odmXML));
		
		
		$xmlName = "sync_".$study."_".$_SESSION['xmlFile'];
		
		//$xml->saveXML("savedxmls/".$xmlName);
		file_put_contents('savedxmls/'.htmlspecialchars($_SESSION['user_name']).'/'.$xmlName,$xml);
		$finalXMLs[$studyName] = $xmlName;
	}

	
}
	

?>


<p>Creating ODM xml ...</p>
<table id="finalXMLTable">
<thead>
<tr class="tableheader"><td>Study/Site</td><td>Import XML</td></tr>

</thead>
<tbody>
<?php 
foreach($finalXMLs as $xkey=>$xname){
		echo '<tr><td>'.$xkey.'</td><td><button type="button" onclick="location.href=\'download.php?type=sync&id='.$xname.'\'">Download '.$xname.'</button></td></tr>';
}

?>

</tbody>
</table>
<br/>



<?php 
require_once 'includes/html_bottom.inc.php';
?> 