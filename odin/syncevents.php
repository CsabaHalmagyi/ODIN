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




<p>Attempting to schedule events...</p>
<table id="eventScheduling">
<thead>
<tr class="tableheader"><td>Subject</td><td>Event</td><td>XML Event OID</td><td>Server Event OID</td><td>Curr. occ</td><td>Max occ. on server</td><td>Status</td></tr>

</thead>
<tbody>
<?php

$ocEventLocation='';
$ocEventStartTime = '00:01';
$ocEventEndDate = '';
$ocEventEndTime = '';

$ocUniqueProtocolId_server = $_SESSION['studyprotname'];

//creating the ODM XML
foreach ($odmMeta_client->ClinicalData as $clientClinicalDataNode){
	
	$siteID_server = null;
	$study = null;
	$studyOID_client = (string)$clientClinicalDataNode->attributes()->StudyOID;
	foreach($_SESSION['syncStudies'] as $sskey=>$ss){
		if($ss['clientoid'] == $studyOID_client){
			$study = $ss['serveroid'];
			if($ss['issite']){
				$prefix = $ocUniqueProtocolId_server." - ";
				$str = $sskey;
				if (substr($str, 0, strlen($prefix)) == $prefix) {
					$siteID_server = substr($str, strlen($prefix));
				}
			}
		}
	}
	
		$dbh = new PDO("pgsql:dbname=$db;host=$dbhost", $dbuser, $dbpass );
		
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
		
//			if ($subjectOID_final == '' || $subjectOID_final == null) continue;
				
			foreach($clientSubjectDataNode->StudyEventData as $clientEventNode){
				$clientEventOID = (string)$clientEventNode->attributes()->StudyEventOID;
				$clientEventName = $events_client[$clientEventOID]['name'];
				$clientEventStartDate = (string)$clientEventNode->attributes('OpenClinica',TRUE)->StartDate;
				$eventOccurrence = $clientEventNode->attributes()->StudyEventRepeatKey;
				$serverEventOID='';
				$isRepeatingEvent = true;
				if (empty($eventOccurrence)) {
					$eventOccurrence = 0;
					$isRepeatingEvent = false;
				}
				//determining the server's event oid
				foreach($events_server as $key=>$val){
					if ($events_server[$key]['name']==$clientEventName){
						$serverEventOID = $key;
						break;
					}
				}
		
				$sql =	"SELECT max(study_event.sample_ordinal) as last_event
					FROM
					public.study_subject
					INNER JOIN
					public.study_event ON study_subject.study_subject_id = study_event.study_subject_id
					INNER JOIN public.study_event_definition ON  study_event.study_event_definition_id = study_event_definition.study_event_definition_id
					AND study_subject.label = '".$subjectID."'
					AND study_event_definition.oc_oid = '".$serverEventOID."'";
					
				$sth = $dbh->prepare($sql);
				$sth->execute();
				$result = $sth->fetch(PDO::FETCH_ASSOC);
		
				$eventOccurrenceOnServer=$result['last_event'];
		
				if ($eventOccurrenceOnServer == ""  || $eventOccurrenceOnServer==null){
					$eventOccurrenceOnServer=0;
				}
		
				$message='Skip scheduling';
				if($eventOccurrence==0 || $eventOccurrence-$eventOccurrenceOnServer==1){
		
		
					$schedule = $meta_server->eventSchedule($subjectID, $serverEventOID,
							$ocEventLocation, $clientEventStartDate, $ocEventStartTime, $ocEventEndDate,
							$ocEventEndTime, $ocUniqueProtocolId_server, $siteID_server);
		
					if ($schedule->xpath('//v1:result')[0]=='Success'){
						$message = 'Event has been scheduled.';
					}
					else{
						$message = $schedule->xpath('//v1:error')[0];
					}
				}
		
				if ($rowCounter%2==0){
					echo '<tr class="even">';
				}
				else{
					echo '<tr class="odd">';
				}
				//$message=$sql;
				echo '<td>'.$subjectID.'</td><td>'.$clientEventName.'</td><td>'.$clientEventOID.'</td><td>'.$serverEventOID.'</td><td>'.$eventOccurrence.'</td><td>'.$eventOccurrenceOnServer.'</td><td>'.$message.'</td></tr>';
				$rowCounter++;
				//var_dump($result);
			}
		}
	
}
	

?>

</tbody>
</table>
<br/>
<?php 
echo "<p><br/>";
echo '<a href="index.php" class="easyui-linkbutton" data-options="iconCls:\'icon-back\'">Go to Main menu</a> OR <a href="synccreatexml.php" class="easyui-linkbutton" data-options="iconCls:\'icon-next\'">Continue to creating import XML</a></p>';

?>
<?php 
require_once 'includes/html_bottom.inc.php';
?> 