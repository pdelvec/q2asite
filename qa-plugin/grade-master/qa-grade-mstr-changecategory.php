<?php
/**
*
*POST $changecategory flag to SESSION
*
**/

$changecategory = $_POST['changecategory'];

 session_start(); 
    $_SESSION['changecategory'] = $changecategory;
    header('Location: qa-grade-mstr-widget.php');


?>