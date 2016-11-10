<?php

require_once 'includes/connection.inc.php';
require_once 'includes/html_top.inc.php';
require_once 'classes/OpenClinicaSoapWebService.php';
require_once 'classes/OpenClinicaODMFunctions.php';

is_logged_in();
require($_SESSION['settingsfile']);
//add the session id to the datafile's name


$meta_server = 	new OpenClinicaSoapWebService($ocWsInstanceURL, $_SESSION ['user_name'], $_SESSION ['passwd']);

$finalSubjects = array();

//$ocUniqueProtocolId_server = $_SESSION['studyprotname'];
$odmMeta_client = simplexml_load_file("uploads/".$_SESSION['xmlFile']);
//$odmMeta_client = simplexml_load_string($odmMetaRaw_client[0]);
$odmMeta_client->registerXPathNamespace('odm', OpenClinicaSoapWebService::NS_ODM);


$studyName_client = $odmMeta_client->Study->GlobalVariables->ProtocolName;

$ocUniqueProtocolId_server = $_SESSION['studyprotname'];

foreach ($odmMeta_client->ClinicalData as $clientClinicalDataNode){
	$siteID_server = null;
		
	$studyOID_client = (string)$clientClinicalDataNode->attributes()->StudyOID;
	foreach($_SESSION['syncStudies'] as $sskey=>$ss){
		if($ss['clientoid'] == $studyOID_client){
			if($ss['issite']){
				$prefix = $ocUniqueProtocolId_server." - ";
				$str = $sskey;
				if (substr($str, 0, strlen($prefix)) == $prefix) {
					$siteID_server = substr($str, strlen($prefix));
				}
				
				
			}
		}
	}
	
	//echo $studyOID_client." = ".$ocUniqueProtocolId_server." + ".$siteID_server."<br/>";
	
	foreach($clientClinicalDataNode->SubjectData as $clientSubjectDataNode){
		
		$subjID = (string)$clientSubjectDataNode->attributes('OpenClinica',TRUE)->StudySubjectID;
		$clientSubjOID = (string)$clientSubjectDataNode->attributes()->SubjectKey;
		
		$isStudySubject = $meta_server->subjectIsStudySubject($ocUniqueProtocolId_server,
				$siteID_server, $subjID);
		
		// if the current subject is existing in OC Server
		if ($isStudySubject->xpath('//v1:result')[0]=='Success'){
			$servStudSubjOID = (string)$isStudySubject->xpath('//v1:subjectOID')[0];
			$finalSubjects[$subjID] = array("subjID"=>$subjID,"serverSubjOID"=>$servStudSubjOID,"clientSubjOID"=>$clientSubjOID,"existed"=>true, "error"=>null);
		
		}
		else{
			//import subject and determine subject OID for $finalSubjects
		
			$personID = (string)$clientSubjectDataNode->attributes('OpenClinica',TRUE)->UniqueIdentifier;
			$secondaryID = (string)$clientSubjectDataNode->attributes('OpenClinica',TRUE)->SecondaryID;
			$DOB = (string)$clientSubjectDataNode->attributes('OpenClinica',TRUE)->DateOfBirth;
			$gender = (string)$clientSubjectDataNode->attributes('OpenClinica',TRUE)->Sex;
			$enrollmentDate = Date('Y-m-d');
		
			if(empty($secondaryID)) $secondaryID=null;
			if(empty($personID)) $personID=null;
			if(empty($DOB)) $DOB=null;
			if(empty($gender)) $gender=null;
				
		
			$createSubject = $meta_server->subjectCreateSubject($ocUniqueProtocolId_server,
					$siteID_server, $subjID, $secondaryID,
					$enrollmentDate, $personID, $gender, $DOB);
		
			//if creation is successful, try to determine the subject's OID
			if ($createSubject->xpath('//v1:result')[0]=='Success'){
		
				$isStudySubject = $meta_server->subjectIsStudySubject($ocUniqueProtocolId_server,
						$siteID_server, $subjID);
		
				if ($isStudySubject->xpath('//v1:result')[0]=='Success'){
					$servStudSubjOID = (string)$isStudySubject->xpath('//v1:subjectOID')[0];
					$finalSubjects[$subjID] = array("subjID"=>$subjID,"serverSubjOID"=>$servStudSubjOID,"clientSubjOID"=>$clientSubjOID,"existed"=>false,"error"=>null);
				}
				else {
					$err = (string)$isStudySubject->xpath('//v1:error')[0];
					$finalSubjects[$subjID] = array("clientSubjID"=>$subjID,"serverSubjOID"=>null,"clientSubjOID"=>$clientSubjOID,"existed"=>false,"error"=>$err);
				}
		
		
			}
			else {
				$err = (string)$createSubject->xpath('//v1:error')[0];
				$finalSubjects[$subjID] = array("clientSubjID"=>$subjID,"serverSubjOID"=>null,"clientSubjOID"=>$clientSubjOID,"existed"=>false,"error"=>$err);
			}
		}
		
		
	}


}


ksort($finalSubjects);

?>
<br/>
Synchronising subjects...
<table id="subjectMap">
<thead>
<tr class="tableheader"><td>#</td><td>Subject ID</td><td>Server Subject OID</td><td>Client Subject OID</td><td>New subject?</td><td>Error</td></tr>

</thead>
<tbody>
<?php 

$rowCounter = 0;
$errors = 0;
foreach($finalSubjects as $skey=>$sval){
	$rowCounter++;
	if(!empty($sval['error'])) $errors++;
	
	if ($rowCounter%2==0){
		echo '<tr class="even">';
	}
	else{
		echo '<tr class="odd">';
	}
	echo '<td>'.$rowCounter.'</td><td>'.$skey.'</td><td>'.$sval['serverSubjOID'].'</td><td>'.$sval['clientSubjOID'].'</td>';
		if ($sval['existed']) echo '<td>NO</td>';
		else echo '<td><span class="">YES</span></td>';
	echo '<td>'.$sval['error'].'</td></tr>'; 
		

}

?>
</tbody>
</table>

<table>
<tr><td>Subjects in XML:</td><td><?php echo $rowCounter;?></td></tr>
<tr><td>Subjects for which data cannot be transferred:</td><td><?php echo $errors;?></td></tr>
</table>
<?php 
$_SESSION['finalSubjects'] = $finalSubjects;
echo "<p><br/>";
echo '<a href="index.php" class="easyui-linkbutton" data-options="iconCls:\'icon-back\'">Go to Main menu</a> OR <a href="syncevents.php" class="easyui-linkbutton" data-options="iconCls:\'icon-next\'">Continue to synchronising events</a></p>';

?>

<?php 
require_once 'includes/html_bottom.inc.php';
?> 