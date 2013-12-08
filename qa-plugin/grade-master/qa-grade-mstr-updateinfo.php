<?php
/*
*
*
*UPDATE WIDGET FILE
*
*
*/
	$username = $_POST['username'];
	$postid = $_POST['postid'];
	
	
	$root = $_SERVER['DOCUMENT_ROOT'];
	//***************CHAAANGEEEE TO ROOT NAME OF SITE LOCATION*********************
	require_once($root.'/q2a/qa-include/qa-base.php');
	
	require_once QA_INCLUDE_DIR.'qa-db-metas.php';
	require_once QA_INCLUDE_DIR.'qa-app-posts.php';

	//******Is User Poster Check**********//
	$post_info = qa_post_get_full($postid);
			$poster_username = qa_db_query_raw('SELECT handle FROM qa_users WHERE userid = '.$post_info['userid'].'');
			$poster_username = mysql_fetch_assoc($poster_username);
			if($username == $poster_username['handle'])
				echo "<font style ='background-color:yellow'>Poster</font>";
	//*************************************************//
				
				
	$userid = qa_db_query_raw('SELECT userid FROM qa_users WHERE handle = "'.$username.'"');
	$userid = mysql_fetch_assoc($userid);
	
	$user_grade = qa_db_query_raw('SELECT grade from qa_grades WHERE userid = "'.$userid['userid'].'" AND postid = "'.$postid.'"');
	$user_grade = mysql_fetch_assoc($user_grade);
	
	//*****************Post Current Grade ifexists****************//
	if($user_grade == false){
		echo '</br>';
		echo 'No Grade Recorded';
	}
	else{
		echo '</br>';
		echo 'Current Grade: '.$user_grade['grade'];
	}
	//***********************************************//
?>





