<?php
require_once 'includes/connection.inc.php';
$_SESSION['task']="loadfromxml";
require_once 'includes/html_top.inc.php';
is_logged_in();
require($_SESSION['settingsfile']);


//var_dump($_SESSION);
?>

<table><tr><td><h3>Synchronising data from xml</h3></td></tr></table>

<?php


//connect to the database
$dbh = new PDO("pgsql:dbname=$db;host=$dbhost", $dbuser, $dbpass );

//return all the active studies the user is assigned
$query = "select s.study_id, s.name, s.unique_identifier, sur.user_name, sur.role_name
from study_user_role sur, study s
where s.study_id = sur.study_id
and sur.user_name = '".trim($_SESSION['user_name'])."' and s.parent_study_id is null 
		and s.status_id=1 and sur.status_id=1";


$sth = $dbh->prepare($query);
$sth->execute();

$studies = $sth->fetchAll(PDO::FETCH_ASSOC);

//return all the active studies
$query = "select s.study_id, s.name, s.unique_identifier from study s
where s.parent_study_id is null
		and s.status_id=1";


$sth = $dbh->prepare($query);
$sth->execute();
$studiesAll = $sth->fetchAll(PDO::FETCH_ASSOC);

?>
<br/>
<label for="studylist">
<form action="uploadxml.php" method="post" enctype="multipart/form-data">

<table id="uploader">
<tr><td>Select a study:</td><td> <select id="studyprotname" name="studyprotname">
<?php 

//display all studies
foreach($studies as $key=>$value){
	echo '<option value="'.$value['unique_identifier'].'">'.$value['name'].'</option>';
}

?>
</select></td></tr>
<tr><td>Please choose an ODM XML file:</td><td> <input type="file" name="uploadFile"></td></tr>

  <tr><td><input type="submit" value="Upload File" class="easyui-linkbutton" data-options="iconCls:'icon-add'"></td></tr>
</table>
  </form> 


</label>
<?php 
require_once 'includes/html_bottom.inc.php';
?>