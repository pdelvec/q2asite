<?php

	class qa_grade_master_usergrades_page {
	//****************START INIT functions**************************//	
		var $directory;
		var $urltoroot;
		
		function load_module($directory, $urltoroot)
		{
			$this->directory=$directory;
			$this->urltoroot=$urltoroot;
		}
		
		function suggest_requests(){
		
			return array(
				array(
					'title' => 'Grades',
					'request' => '?qa=grades',
					'nav' => 'M',
					),
			);
		
		}
		
		function match_request($request)
		{
			if ($request == 'grades')
				return true;
			
			return false;
		}
	//*******************************END INIT functions*******************************//
		function process_request($request)
		{
		
		$allowediting=!qa_user_permit_error('plugin_grade_mstr_permit_edit');
		
		$qa_content=qa_content_prepare();
		
		//************LOGIN CHECK***********************//
		require_once QA_INCLUDE_DIR.'qa-app-users.php';
		
        if(!qa_is_logged_in()) //make sure user is an admin to access page
			return $qa_content;
		//********************************************//
		
		//*****************************Show/Hide Categories/Posts dropdown Script*************//
		echo '<script>',
		'function updateview(view_selected){',
					'if(view_selected == "post"){',
						'$("#categories").hide();',
						'$("#posts").show();',
					'}',
					'else if(view_selected == "category"){',
						'$("#posts").hide();',
						'$("#categories").show();',
					'}',
				'}',
				'</script>';
		//*****************************************************************************//
		
		//**********************Update posts/categories table Script*******************//
		echo '<script>',
					'function updatetable(value_selected,type_selected){',
					'if(type_selected == 0){',
					'	var id = $("#categories_dropdown").children(":selected").attr("id");',
					'}',
					'else',
					'	var id = $("#posts_dropdown").children().children(":selected").attr("id");',
					'$.ajax({',
					'type: "POST",',
					'url: "qa-plugin/grade-master/qa-grade-mstr-update_admin_grade_table.php",',
					'data: {value_selected:value_selected,type_selected:type_selected,id:id},',
					'success: function (response) {',
						'$("#updatetable").empty();',
						'$("#updatetable").append(response)',
						'},',
						'error: function (response) {',
						'alert("Error getting php file");',
						'}',
						'});',
					'}',
		'</script>';
		//******************************************************************************//
		
		//*************************Submit Extra Grade Script***************************//
		echo '<script>',
			'function submitgrade(userid,username,categoryid){',	
			'var grade = $(username).val();',
			'	$.ajax({',
					'type: "POST",',
					'url: "qa-plugin/grade-master/qa-grade-mstr-insert-grd.php",',
					'data: {userid:userid,grade:grade,categoryid:categoryid,username:username},',
					'success: function (response) {',
						'alert("Grade Submitted");',
						'},',
						'error: function (response) {',
						'alert("Error getting php file");',
						'}',
				'});',
			'}',
			'</script>';
		//*********************************************************************************//
		
		//****************************Show input extra grade field Script************************//
		echo '<script>',
			'function showgradefield(tablereveal,gradehide){',
			'$(gradehide).empty();',
			'$(tablereveal).show();',
			'}',
			'</script>';
		//**********************************************************************************//
		
		
		//******************ADMIN PAGE****************************//
		if($allowediting){
		
		
		$qa_content['title']= 'Manage Grades';
		
		//Options = Posts,Categories
		$page_build = build_manager_options();
		$page_build .= '<div id = "updateview">';
		$page_build .= '</div>';
		
		//******************Category dropdown build************************************//
		$categoryoptions = array();
		$name = 'categories_dropdown';
		$selected = 0;
		$select = 0;
		
		$posts = qa_db_query_raw('SELECT title, categoryid FROM qa_categories');
		while($row = mysql_fetch_assoc($posts))
			array_push($categoryoptions,$row['title'].'/'.$row['categoryid']);
		
		$page_build .= '<span id = "categories" style="display:none;">Select Category: '.dropdown2($name,$categoryoptions,$selected,$select).'</span>';
		//***********************************************************************//
		
		//********************Posts dropdown build**********************************//
		$postoptions = array();
		$name = 'posts_dropdown';
		$selected = 0;
		$select = 1;
		
		$posts = qa_db_query_raw('SELECT p.title, p.postid,p.categoryid,q.title as ctitle FROM qa_posts p, qa_categories q WHERE type = "Q" AND p.categoryid = q.categoryid ORDER BY categoryid, postid');
		while($row = mysql_fetch_assoc($posts))
			array_push($postoptions,$row['title'].'/'.$row['postid'].'/'.$row['ctitle']);
		
		$page_build .= '<span id = "posts" style = "display:none;">Select Post: '.dropdown2($name, $postoptions,$selected,$select).'</span>';
		
		$page_build .= '<div id = "updatetable" style = "overflow-x: scroll;">';
		$page_build .= '</div>';
		//**************************************************************************//
		
		
		$qa_content['custom'] = $page_build;
		
		}
		//********************************************************//
		
		//****************USER PAGE*****************************//
		else{
		$qa_content['title'] = qa_get_logged_in_handle()."'s Grades";
		require_once QA_INCLUDE_DIR.'qa-db-metas.php';
		require_once QA_INCLUDE_DIR.'qa-app-users.php';
		
		$user = qa_get_logged_in_userid();
		
		
		$qa_content['custom'] = build_user_grade_table($user);
		
		
		}
		//*****************************************************//
		
		return $qa_content;
			
		}
	
}

function build_user_grade_table($user){
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
			
			//initialize db_queries
			$gradeable_posts = qa_db_query_raw("SELECT title,postid,categoryid,userid FROM qa_posts WHERE type = 'Q' AND categoryid IS NOT NULL ORDER BY categoryid, postid");
			$graded_posts = qa_db_query_raw("SELECT postid, grade FROM qa_grades WHERE userid = '".$user."' AND postid > 0 AND categoryid IS NOT NULL ORDER BY categoryid, postid");
			$user_posts = qa_db_query_raw("SELECT postid,parentid FROM qa_posts WHERE userid = '".$user."' AND categoryid IS NOT NULL ORDER BY categoryid, postid");
			$extra_grades = qa_db_query_raw("SELECT grade,postid,categoryid FROM qa_grades WHERE userid = '".$user."' AND postid = 0 AND categoryid IS NOT NULL ORDER BY categoryid, postid");
			
			
			require_once QA_INCLUDE_DIR.'qa-base.php';
			$qa_home = qa_path_to_root();
			
			//begin table
			$table = '<table border = "2" CELLSPACING="3" CELLPADDING="3""><tr><th>Post</th><th>Grade</th></tr>';
			
			//$g_posts = graded posts fields
			$g_posts = array();
			while($row = mysql_fetch_assoc($graded_posts)){
				array_push($g_posts,$row['postid']);
				array_push($g_posts,$row['grade']);
			}
			
			//$u_posts = user posts/answers fields
			$u_posts = array();
			while($row = mysql_fetch_assoc($user_posts)){
				array_push($u_posts,$row['postid']);
				array_push($u_posts,$row['parentid']);
			}
			
			//$e_grades = extra grade field
			$e_grades = array();
			while($row = mysql_fetch_assoc($extra_grades)){
				array_push($e_grades,$row['postid']);
				array_push($e_grades,$row['grade']);
				array_push($e_grades,$row['categoryid']);
			}
			
			/*INITIALIZE VARIABLES*
			*$i = index for iterating g_posts and updating $grademarker
			*$categoryid = variable for checking which category iteration is on
			*$j = index for iterating u_posts
			*$extragrade = initializing extragrade variable
			*$gradeflag = flag for first category post
			*$grademarker = index updated with $i in order to access correct grades for total
			*$z = index for finding users extragrade if in database
			**/
			$i = 0;
			$categoryid = 1;
			$j = 0;
			$extragrade = 0;
			$gradeflag = 0;
			$grademarker = 0;
			$z = 0;
			while ($row = mysql_fetch_assoc($gradeable_posts)){
				//First Category Build
				if($categoryid == $row['categoryid']){
					//set to 1 after first category is posted
					if($gradeflag != 0){
						//******Calculate Total******//
						$total = 0;
						for($k = $grademarker; $k<$i; $k++){
							$total += $g_posts[++$k];
						}
						//Extra grade for user?
						while($z < sizeof($e_grades)){
							if($row['categoryid'] == $e_grades[$z+2]){
								$extragrade = $e_grades[$z+1];
								$z += 3;
							}
							else
								$z += 3;
						}
						$table.= '<tr><td>Extra Grade(For upvotes, comments, etc.)</td><td>'.$extragrade.'</td></tr>';
						$total += $extragrade;
						//*******************//
						//Reset variables
						$extragrade = 0;
						$z = 0;
						$table.= '<tr><th>Total:</th><th>'.$total.'</th></tr>';
						//Set new starting point for g_posts total calculator
						$grademarker = $i;
					}
					//****Post Category Title****//
					$category = qa_db_query_raw("SELECT title FROM qa_categories WHERE categoryid = '".$categoryid."'");
					$category = mysql_fetch_assoc($category);
					$table.= '<tr><th><u>'.$category['title'].'</u></th><tr>';
					//*****************//
					$categoryid++;
					$gradeflag = 1;
				}
				//Next Category Build
				if($row['categoryid'] != $categoryid-1){
					//*******Calculate Total****//
					if($gradeflag != 0){
						$total = 0;
						for($k = $grademarker; $k<$i; $k++){
							$total += $g_posts[++$k];
						}
						//extra grade for user?
						while($z < sizeof($e_grades)){
							if($row['categoryid'] == $e_grades[$z+2]){
								$extragrade = $e_grades[$z+1];
								$z += 3;
							}
							else
								$z += 3;
						}
						$table.= '<tr><td>Extra Grade(For upvotes, comments, etc.)</td><td>'.$extragrade.'</td></tr>';
						$total += $extragrade;
						//************************//
						//Reset Variables
						$extragrade = 0;
						$z = 0;
						
						$table.= '<tr><th>Total:</th><th>'.$total.'</th></tr>';
						//Set new starting point for g_posts total calculator
						$grademarker = $i;
					}
					//************Post Category Title*************//
					$categoryid = $row['categoryid'];
					$category = qa_db_query_raw("SELECT title FROM qa_categories WHERE categoryid = '".$categoryid."'");
					$category = mysql_fetch_assoc($category);
					$table.= '<tr><th><u>'.$category['title'].'</u></th><tr>';
					//**************************************//
		
					$categoryid++;
					$gradeflag = 1;
				}
				//****************Print Grades**********************//
				//if Grade for post --> print grade
				if($i<sizeof($g_posts) && $row['postid'] == $g_posts[$i]){
					$table .= '<tr><td style = "max-width:400px;word-wrap:break-word;"><A HREF ="'.$qa_home.'?qa='.$row['postid'].' ">'.$row['title'].'</td><td>'.$g_posts[++$i].'</a></td></tr>';
					$i++;
					if($j<sizeof($u_posts) && ($row['postid'] == $u_posts[$j+1] || $row['postid'] == $u_posts[$j])){
						$j++;
						$j++;
					}
				}
				else{
					//If post but no grade
					if($j<sizeof($u_posts) && ($row['postid'] == $u_posts[$j+1] || $row['postid'] == $u_posts[$j])){
						$table .= '<tr><td style = "max-width:400px;word-wrap:break-word;"><A HREF ="'.$qa_home.'?qa='.$row['postid'].'">'.$row['title'].'</a></td><td>No Grade</td></tr>';
						$j++;
						$j++;
					}
					//If no post
					else{
						$table .= '<tr><td style = "max-width:400px;word-wrap:break-word;"><A HREF ="'.$qa_home.'?qa='.$row['postid'].'">'.$row['title'].'</a></td><td>Unanswered</td></tr>';
					}
				}
				//***************************************************//
			}
			if($gradeflag != 0){
						//******Calculate last Total********************//
						$total = 0;
						for($k = $grademarker; $k<$i; $k++){
							$total += $g_posts[++$k];
						}
						
						//Extra grade?
						while($z < sizeof($e_grades)){
							if($categoryid-1 == $e_grades[$z+2]){
								$extragrade = $e_grades[$z+1];
								$z += 3;
							}
							else
								$z += 3;
						}
						$table.= '<tr><td>Extra Grade(For upvotes, comments, etc.)</td><td>'.$extragrade.'</td></tr>';
						$total += $extragrade;
						//***************************************//
						
						//Reset Variables
						$extragrade = 0;
						$z = 0;
						
						$table.= '<tr><th>Total:</th><th>'.$total.'</th></tr>';
						$grademarker = $i;
					}
			
			//close table
			$table .= '</table>';
			
			return $table;
		
}

function build_manager_options(){
	$options = "View Grades By: ";
	$options .= '<select id = "viewoption" onchange = "updateview(this.options[this.selectedIndex].value)">'."\n";
	$options .= '<option value ="category">Category</option>';
	$options .= '<option value ="post">Post</option>';
	$options .= '</select>';
	
	return $options;
}

//Dropdown for posts/categories
function dropdown2( $name, array $options, $selected=null, $select)
{
    /*** begin the select ***/
	//Function updatetable params = Selected dropdown field, 1:Posts/2:Categories
    $dropdown = '<select id = "'.$name.'" name="'.$name.'" style = "max-width:200px;" id="'.$name.'" onchange="updatetable(this.options[this.selectedIndex].value,'.$select.')">'."\n";
	
	
    $selected = $selected;
    /*** loop over the options ***/
	//**********Posts Build***************************//
	if($select == 1){
	//flag for first optgroup label div start
	$flag = 0;
	foreach( $options as $key=>$option )
    {
		$select = $selected==$key ? ' selected' : null;
		/**
		*$pieces[0] = Post Title
		*$pieces[1] = Postid
		*$pieces[2] = Category Title
		**/
		$pieces = explode("/",$options[$key]);
		
		if($flag == 0){
			$dropdown .= '<optgroup label = "'.$pieces[2].'">';
			$flag = 1;
		}
		else{
			$prev_pieces = explode("/",$options[$key-1]);
			if($pieces[2] != $prev_pieces[2]){
				$dropdown .= '</optgroup>';
				$dropdown .= '<optgroup label = "'.$pieces[2].'">';
			}
		}
		$dropdown .= '<option id = "'.$pieces[1].'" value="'.$pieces[0].'"'.$select.'>'.$pieces[0].'</option>'."\n";
	}
	}
	
	//*********************Categories Build*******************************//
	else{
		foreach( $options as $key=>$option )
		{
		$select = $selected==$key ? ' selected' : null;
		$pieces = explode("/",$options[$key]);
		/**
		*$pieces[0] = Category Title
		*$pieces[1] = Category id
		**/
		$dropdown .= '<option id = "'.$pieces[1].'" value="'.$pieces[0].'"'.$select.'>'.$pieces[0].'</option>'."\n";
		}
	}
	

    /*** close the select ***/
    $dropdown .= '</select>'."\n";

    /*** and return the completed dropdown ***/
    return $dropdown;
}
/*
	Omit PHP closing tag to help avoid accidental output
*/