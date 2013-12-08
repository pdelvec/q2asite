<?php
/**
*
*INSERT EXTRA GRADE FILE
*POSTID = 0
*
**/
	$userid = $_POST['userid'];
	$grade = $_POST['grade'];
	$categoryid = $_POST['categoryid'];
	$username = $_POST['username'];
	
	$root = $_SERVER['DOCUMENT_ROOT'];
	require_once($root.'/q2a/qa-include/qa-base.php');
	
	require_once QA_INCLUDE_DIR.'qa-db-metas.php';
	require_once QA_INCLUDE_DIR.'qa-app-posts.php';
	
	qa_db_query_raw("DELETE FROM qa_grades WHERE userid = '".$userid."' AND postid = 0 AND categoryid = '".$categoryid."'");
	qa_db_query_raw("INSERT INTO qa_grades (userid,grade,postid,categoryid)
								VALUES ('".$userid."','".$grade."','0','".$categoryid."')");
	
?>