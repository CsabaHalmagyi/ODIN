<?php

require_once 'includes/connection.inc.php';
require_once 'includes/html_top.inc.php';
is_logged_in();
require($_SESSION['settingsfile']);
//add the session id to the datafile's name
$target_dir = "uploads/";
$target_dir = $target_dir . basename( $_FILES["uploadFile"]["name"]);
$uploadOk=1;

//var_dump($_FILES);

?>




<?php


if (isset($_POST['studyprotname']) && strlen($_POST['studyprotname'])>0){
	// if study+site reference was submitted
	if (strpos($_POST['studyprotname'],'##')!==false){
		$studydata = explode('##',$_POST['studyprotname']);
		$_SESSION['studyprotname'] = $studydata[0];
		$_SESSION['siteprotname'] = $studydata[1];	
	}
	else {
		$_SESSION['studyprotname'] = $_POST['studyprotname'];
	}


}
else {
    echo "Missing study name!";
    $uploadOk = 0;
}







// Check if $uploadOk is set to 0 by an error
if ($uploadOk == 0) {
    echo "<p>Sorry, your file was not uploaded.<br/>";
    echo '<a href="index.php" class="easyui-linkbutton" data-options="iconCls:\'icon-back\'">Go back</a></p>';
// if everything is ok, try to upload file
} else {
    if (move_uploaded_file($_FILES["uploadFile"]["tmp_name"], $target_dir)) {
        echo "<p>The file ". basename( $_FILES["uploadFile"]["name"]). " has been uploaded.<br/><br/>";
        echo '<a href="index.php" class="easyui-linkbutton" data-options="iconCls:\'icon-back\'">Go back</a> or <a href="syncmap.php" class="easyui-linkbutton" data-options="iconCls:\'icon-next\'">Continue to mapping</a></p>';
      $_SESSION['xmlFile']=$_FILES["uploadFile"]["name"];
    } else {
        echo "<p>Sorry, there was an error uploading your file.<br/>";
            echo '<a href="index.php" class="easyui-linkbutton" data-options="iconCls:\'icon-back\'">Go back</a></p>';
    }
}
?> 
 
<?php 
require_once 'includes/html_bottom.inc.php';
?> 
 