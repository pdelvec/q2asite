<?php
	$type_selected = $_POST['type_selected'];
	$value_selected = $_POST['value_selected'];
	$id = $_POST['id'];
	
	$root = $_SERVER['DOCUMENT_ROOT'];
	//***************CHAAANGEEEE TO ROOT NAME OF SITE LOCATION*********************
	require_once($root.'/q2a/qa-include/qa-base.php');
	
	require_once QA_INCLUDE_DIR.'qa-db-metas.php';
	require_once QA_INCLUDE_DIR.'qa-app-posts.php';
	
	//*******************FIX Category Deletions**************************************//
		
			//fix category deletions
			$wrongcategory_grades = qa_db_query_raw("SELECT postid,categoryid from qa_grades WHERE categoryid NOT IN (SELECT categoryid FROM qa_categories) AND postid > 0");
			while($row = mysql_fetch_assoc($wrongcategory_grades)){
				qa_db_query_raw("UPDATE qa_grades
								SET categoryid = (SELECT categoryid from qa_posts WHERE postid = '".$row['postid']."')
								WHERE categoryid = '".$row['categoryid']."'
			");
			}
		
		//**********************************************************************//
	
	
	//Category
	if($type_selected == 0){
		echo build_category_grade_table($id);
	}
	//Post
	if($type_selected == 1){
		echo build_post_grade_table($id);
	}
	
	
	//BUILD POST TABLE
	function build_post_grade_table($postid){
		$postinfo = qa_post_get_full($postid);
		$qa_home = qa_path_to_root();
		
		//Is the poster a student?
		$posterlevel = qa_db_query_raw("SELECT level FROM qa_users WHERE userid = '".$postinfo['userid']."'");
		$posterlevel = mysql_fetch_assoc($posterlevel);
		//If poster is student, select by parentid AND postid
		if($posterlevel['level'] < 120){
			$user_answers = qa_db_query_raw("SELECT p.userid,u.handle FROM qa_posts p, qa_users u WHERE u.level < 120 AND (p.parentid = '".$postinfo['postid']."' OR p.postid = '".$postinfo['postid']."') AND u.userid = p.userid ORDER BY p.userid");
		}
		//If poster is professor, select by parentid
		else{
			$user_answers = qa_db_query_raw("SELECT p.userid,u.handle FROM qa_posts p, qa_users u WHERE u.level < 120 AND p.parentid = '".$postinfo['postid']."' AND u.userid = p.userid ORDER BY p.userid");
		}
		
		$graded_answers = qa_db_query_raw("SELECT g.grade, u.handle, g.userid FROM qa_grades g,qa_users u WHERE postid = '".$postinfo['postid']."' AND g.userid = u.userid AND postid > 0 ORDER BY userid");
	
		$table = '<table border = "2" CELLSPACING="3" CELLPADDING="3"><tr><th style = "max-width: 700px; word-wrap:break-word;">Title: <A HREF ="'.$qa_home.'?qa='.$postinfo['postid'].'">"'.$postinfo['title'].'"</a></th></tr>';
		
		//Array for Graded answers
		$g_answers = array();
		while($row = mysql_fetch_assoc($graded_answers)){
			array_push($g_answers,$row['userid']);
			array_push($g_answers,$row['handle']);
			array_push($g_answers,$row['grade']);
		}
		
		//If no answers
		if(mysql_num_rows($user_answers) == 0){
			$table.= '<tr><td>No Responses</td></tr>';
		}
		else{
			//************************Print Username/Grade*************************//
			$i = 0;
			$table .= '<tr><th>Username</th><th>Grade</th></tr>';
			while($row = mysql_fetch_assoc($user_answers)){
				if($i<sizeof($g_answers) && $g_answers[$i] == $row['userid']){
					$i++;
					$table.= '<tr><td>'.$row['handle'].'</td><td>'.$g_answers[++$i].'</td></tr>';
					$i++;
				}
				else{
					$table.= '<tr><td>'.$row['handle'].'</td><td>No Grade Recorded</td></tr>';
				}
			}
			//********************************************************************//
		}
		
		$table .= '</table>';
		
		
		return $table;
	}
	
	
	
	//BUILD CATEGORY TABLE
	function build_category_grade_table($categoryid){
		$qa_home = qa_path_to_root();
		$posts = qa_db_query_raw ('SELECT postid, title FROM qa_posts WHERE categoryid = "'.$categoryid.'" AND type = "Q" ORDER BY postid');
		$users = qa_db_query_raw('SELECT u.userid, u.handle FROM qa_users u WHERE u.level <120 ORDER BY u.userid');
		$graded_posts = qa_db_query_raw('SELECT postid,userid,grade FROM qa_grades WHERE categoryid = "'.$categoryid.'" AND postid > 0 ORDER BY userid,postid');
		
		
		$category = qa_db_query_raw('SELECT title FROM qa_categories WHERE categoryid = "'.$categoryid.'"');
		$category = mysql_fetch_assoc($category);
		
		$table = '<table border = "2" CELLSPACING="3" CELLPADDING="3"><tr><th>'.$category['title'].'</th></tr>';
		
		//If No questions with this category
		if(mysql_num_rows($posts) == 0){
			$table .= '<tr><td>No Questions With This Category</td></tr>';
		}
		//If No students have responded to questions in this category
		else if(mysql_num_rows($users) == 0){
			$table .= '<tr><td>No Student Responses</td></tr>';
		}
		else{
		
		//*****************Sakai CSV file --> Array: Sakai_array******************//
		$fileexists = true;
		if(!file_exists($category['title'].".csv"))
		{
		$fileexists = false;
		}
		else
		{
		$fileexists = true;
		$sakai_file = fopen($category['title'].".csv","r");
		$sakai_array = array();
		while(! feof($sakai_file)){
			$row = fgetcsv($sakai_file);
			array_push($sakai_array,$row[0]);
			array_push($sakai_array,$row[1]);
			array_push($sakai_array,$row[2]);
			array_push($sakai_array,$row[3]);
			array_push($sakai_array,$row[4]);
		}
		fclose($sakai_file);
		}
		//******************************************************//
		
		
		//ALL posts under selected category
		$a_posts = array();
		while($row4 = mysql_fetch_assoc($posts)){
			array_push($a_posts,$row4['postid']);
			array_push($a_posts,$row4['title']);
		}
		
		//All graded posts under selected category
		$g_posts = array();
		while($row3 = mysql_fetch_assoc($graded_posts)){
			array_push($g_posts,$row3['postid']);
			array_push($g_posts,$row3['userid']);
			array_push($g_posts,$row3['grade']);
		}
		
		//****************Build Header******************************//
		$table .= '<tr>';
		$table .= '<th>User/Title</th><th>Total</th><td style = "min-width: 150px;">Input Additional Grade(For upvotes, edits, comments etc.)</td>';
		for($i = 0; $i<sizeof($a_posts); $i+=2){
			$table .= '<td style = "max-width: 100px;word-wrap:break-word;"><A HREF ="'.$qa_home.'?qa='.$a_posts[$i].'">'.$a_posts[$i+1].'</td>';
		}
		$table .= '</tr>';
		//***************************************************//
		
		/*
		*$j = index for g_posts
		*$grade_total = variable to store total grade for each user
		*/
		$j = 0;
		$grade_total = 0;
		while($row = mysql_fetch_assoc($users)){
			
			//*******Insert Grades $sakai_array*******************//
			$user_info = qa_db_query_raw('SELECT handle, (SELECT SUM(grade) FROM qa_grades WHERE userid = "'.$row['userid'].'" AND categoryid = "'.$categoryid.'") as grade FROM qa_users WHERE level <120 AND userid = "'.$row['userid'].'"');
			while($row1 = mysql_fetch_assoc($user_info)){
				if($fileexists){
				for($sakai = 15; $sakai<sizeof($sakai_array); $sakai+=5){
						if($sakai_array[$sakai] == $row1['handle'])
							$sakai_array[$sakai+4] = $row1['grade'];
				}
			}
			}
			//*************************************************//
			
			$table .= '<tr>';
			
			//User name
			$table .= '<td>'.$row['handle'].'</td>';
			
			//***************Total Grade*********************//
			for($i = 0; $i <sizeof($g_posts); $i+=3){
				if($g_posts[$i+1] == $row['userid']){
					$grade_total += $g_posts[$i+2];
				}
			}
			//***********************************************//
			
			//**************Extra Grade********************//
			$extragrade = qa_db_query_raw('SELECT grade FROM qa_grades WHERE userid = "'.$row['userid'].'" AND postid = 0 AND categoryid = "'.$categoryid.'"');
			$extragrade = mysql_fetch_assoc($extragrade);
			
			
			//If extra grade exists --> Display Grade, Change Grade Button, Submit Grade textbox, Submit Grade button
			if($extragrade != false){
				$grade_total += $extragrade['grade'];
				$table .= '<td>'.$grade_total.'</td>';
				$grade_total = 0;
				//Display Grade
				$table.= '<td align = "center"><span id = '.$row['userid'].$row['handle'].'>'.$extragrade['grade'];
				//Form id
				$tablereveal = '"#'.$row['handle'].$row['userid'].'"';
				//Grade id
				$gradehide = '"#'.$row['userid'].$row['handle'].'"';
				//Grade Button
				$table .= "<button type = 'button' name = 'changegrade' onclick = 'showgradefield(".$tablereveal.",".$gradehide.")'>Change</button></span>";
				//Submit Grade Textbox
				$table .= '<form id = "'.$row['handle'].$row['userid'].'" style = "display: none;" align = "center">';
				$table .= '<input type = "text" style = "width:50px;" id = "'.$row['handle'].'" name = "grade"/>';
				//input id
				$gradegrab = '"#'.$row['handle'].'"';
				//Submit Grade button
				$table .= "<button type = 'button' name = 'ginsert' onclick = 'submitgrade(".$row["userid"].",".$gradegrab.",".$categoryid.")'>Submit</button>";
				$table .= '</form></td>';
				
			}
			else{
			$table .= '<td>'.$grade_total.'</td>';
			$grade_total = 0;
			//Submit Grade Textbox
			$table .= '<td align = "center"><form>';
			$table .= '<input type = "text" style = "width:50px;" id = "'.$row['handle'].'" name = "grade"/>';
			//input id
			$gradegrab = '"#'.$row['handle'].'"';
			//Submit Grade Button
			$table .= "<button type = 'button' name = 'ginsert' onclick = 'submitgrade(".$row["userid"].",".$gradegrab.",".$categoryid.")'>Submit</button>";
			$table .= '</form></td>';
			}
			//**********************************************//
			
			
			//*****************Print Grades*****************//
			for($i = 0; $i < sizeof($a_posts); $i+=2){
				$user_post = qa_db_query_raw('SELECT userid from qa_posts WHERE categoryid = "'.$categoryid.'" AND userid = "'.$row['userid'].'" AND (parentid = "'.$a_posts[$i].'" OR postid = "'.$a_posts[$i].'")');
				if($j < sizeof($g_posts) && $g_posts[$j] == $a_posts[$i] ){
					$table .= '<td>'.$g_posts[$j+2].'</td>';
					$j += 3;
				}
				else{
					if(mysql_fetch_assoc($user_post) != false)
						$table .= '<td>Ungraded</td>';
					else
						$table .= '<td>Unanswered</td>';
				}
			}
			//*********************************************//
			$table .= '</tr>';
			
		}
		
		
		$table .= '</table>';
		
		//If Sakai CSV file exists
		if($fileexists){
		
		//***********DOWNLOAD CSV FILE WITH GRADES***********************//
		$csv_file = array();
		for($i = 0; $i<sizeof($sakai_array); $i+=5){
			$array = array();
			array_push($array,$sakai_array[$i]);
			array_push($array,$sakai_array[$i+1]);
			array_push($array,$sakai_array[$i+2]);
			array_push($array,$sakai_array[$i+3]);
			array_push($array,$sakai_array[$i+4]);
			array_push($csv_file,$array);
		}
		$fp = fopen($category['title'].'+Grades.csv', 'w');
			foreach($csv_file as $fields) {
				fputcsv($fp, $fields);
		}	
			
		fclose($fp);
		$table .= '<br>';
		$table .= '<br>';
		$table .= '<form method="get" action="qa-plugin/grade-master/'.$category['title'].'+Grades.csv">';
		$table .= '<button type="submit">Download CSV File</button>';
		$table .= '</form>';
		$table .= '<h4>OR (If roster has changed)</h4>';
		$table .= '<form action="qa-plugin/grade-master/qa-grade-mstr-upload_file.php" method="post"enctype="multipart/form-data">';
			$table .= '<label for="file">Upload NEW Sakai CSV File:</label>';
			$table .= '<input type="file" name="file" id="file"><br>';
			$table .= '<input type="submit" name="submit" value="Upload">';
			$table .= '</form>';
		}
		//************************************************************//
		
		//*****************Upload Sakai CSV File*********************//
		else{
			$table .= '<form action="qa-plugin/grade-master/qa-grade-mstr-upload_file.php" method="post"enctype="multipart/form-data">';
			$table .= '<label for="file">Upload Sakai CSV File:</label>';
			$table .= '<input type="file" name="file" id="file"><br>';
			$table .= '<input type="submit" name="submit" value="Upload">';
			$table .= '</form>';
		}
		//***********************************************************//
		return $table;
		
		}
		
		$table .= '</table>';
		
		return $table;
	
	
	}

?>