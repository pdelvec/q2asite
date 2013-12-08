<?php

class qa_grade_master_widget {
	
	//Where will the module be allowed to be placed?
	//See http://www.question2answer.org/modules.php?module=widget for different $template options
	function allow_template($template)
	{
		return ($template=='question');
	}
	
	//Allow region allows you to edit where on the page the widget is allowed to be placed.
	//Return True = all regions
	function allow_region($region)
	{
		return true;
	}
	
	//This is the code for creating the widget content
	function output_widget($region, $place, $themeobject, $template, $request, $qa_content)
	{
		require_once QA_INCLUDE_DIR.'qa-db-metas.php';
		require_once QA_INCLUDE_DIR.'qa-app-posts.php';
		
		//returns false if user does not have high enough permissions
		//SEE function option_default()
		$allowediting=!qa_user_permit_error('plugin_grade_mstr_permit_edit');
		
		if($allowediting){
			require_once QA_INCLUDE_DIR.'qa-db.php';
			
			//what is in the postid from the URL
			$parts = explode ('/',$request);
			$postid = $parts[0];
			

			//select all Userids who have answered the question w/o duplicates
			$users_answered_id = qa_db_query_raw('SELECT DISTINCT userid FROM qa_posts WHERE parentid = '.$postid.' OR postid = '.$postid.'');
			
			//dropdown menu fields
			$options = array();
			$name = 'my_dropdown';
			$selected = 0;
			
			//get the username from the userids
			//push to $options array for dropdown
			while ($row = mysql_fetch_assoc($users_answered_id)) {
				$user_answered = qa_db_query_raw('SELECT handle FROM qa_users WHERE userid = '.$row['userid'].' AND level < 120');
				while($row = mysql_fetch_assoc($user_answered))
					array_push($options,$row['handle']);
			}
			//No widget if no answers
			if(sizeof($options)!=0){
			
			//begin widget
			echo '<div id = "grade_widget">';
			echo '<h3>Input Grades</h3>';
			
			//Grade input Form
			echo '<form name = grade_form id = "grade_submit" action = "" onsubmit="return validate()" method = "post">';
			//SEE function dropdown()
			echo 'User: ';
			echo dropdown( $name, $options, $selected,$postid);
			
			
			//***********Is first user in drop-down the poster/have they been graded on this question yet?******//
			echo '<span id="updateinfo">';
			$post_info = qa_post_get_full($postid);
			$poster_username = qa_db_query_raw('SELECT handle FROM qa_users WHERE userid = '.$post_info['userid'].'');
			$poster_username = mysql_fetch_assoc($poster_username);
			if($options[0] == $poster_username['handle'])
				echo "<font style ='background-color:yellow'>Poster</font>";
			$userid = qa_db_query_raw('SELECT userid FROM qa_users WHERE handle = "'.$options[0].'"');
			$userid = mysql_fetch_assoc($userid);
			$user_grade = qa_db_query_raw('SELECT grade from qa_grades WHERE userid = "'.$userid['userid'].'" AND postid = "'.$postid.'"');
			$user_grade = mysql_fetch_assoc($user_grade);
			if($user_grade == false){
			echo '</br>';
			echo 'No Grade Recorded';
			}
			else{
				echo '</br>';
				echo 'Current Grade: '.$user_grade['grade'];
			}
			echo '</span>';
			//*************************************************************************************//
			
			//**********************Update changecategory, Dynamic drop-down update******************************//
			echo '<script>';
			echo '$(document).ready(function(){',
					'var changecategory = "false";',
					'$.ajax({',
						'type: "POST",',
						'url: "qa-plugin/grade-master/qa-grade-mstr-changecategory.php",',
						'data: {changecategory:changecategory},',
						'success: function () {',
						'},',
						'error: function () {',
						'alert("Error getting php file");',
						'}',
					'});',
				'});',
				'function updateinfo(user_selected,postid){',
					'$.ajax({',
					'type: "POST",',
					'url: "qa-plugin/grade-master/qa-grade-mstr-updateinfo.php",',
					'data: {username:user_selected,postid:postid},',
					'success: function (response) {',
						'$("#updateinfo").empty();',
						'$("#updateinfo").append(response)',
						
						'},',
						'error: function (response) {',
						'alert("Error getting php file");',
						'}',
						'});',
					'}',
					'</script>';
					
			echo '</br>';
			//**********************************************************************//
			
			//******************create Category dropdown/field****************************//
			//dropdown menu variables
			$categoryoptions = array();
			$name = 'my_categorydropdown';
			$selected = 0;
			
			//get the categories
			//insert in $options for category dropdown
			$categories = qa_db_query_raw('SELECT title FROM qa_categories');
			while ($row = mysql_fetch_assoc($categories)) {
				array_push($categoryoptions,$row['title']);
			}
			echo 'Current Category: ';
			if($post_info['categoryid'] !=NULL){
				$current_category = qa_db_query_raw('SELECT title FROM qa_categories WHERE categoryid = "'.$post_info['categoryid'].'"');
				$current_category = mysql_fetch_assoc($current_category);
				echo $current_category['title'];
			}
			else{
				echo 'No Category';
			}
			echo '</br>';
			
			//***********************Change Category Button script***************************//
			echo '<button type = button id = "category_button">Change Category</button>';
			echo '<script>',
				 '$("#category_button").click(function(){',
					'$("#category_button").hide();',
					'$("#category_dropdown").show();',
					'var changecategory = "true";',
					'$.ajax({',
						'type: "POST",',
						'url: "qa-plugin/grade-master/qa-grade-mstr-changecategory.php",',
						'data: {changecategory:changecategory},',
						'success: function() {',
						'},',
						'error: function() {',
						'alert("Error getting php file");',
						'}',
					'});',
					'$("#cancel_button").show()',
				'});',
				'</script>';
			//*********************************************************************************//
			
			echo '<span id = "category_dropdown" style="display: none;">'.dropdown($name, $categoryoptions, $selected, false).'</span>';
			echo '<button type = button id = "cancel_button" style = "display: none;">Cancel</button>';
			
			//*****************************Cancel Button Script****************************//
			echo '<script>',
				'$("#cancel_button").click(function(){',
					'$("#cancel_button").hide();',
					'$("#category_dropdown").hide();',
					'$("#category_button").show();',
					'var changecategory = "false";',
					'$.ajax({',
						'type: "POST",',
						'url: "qa-plugin/grade-master/qa-grade-mstr-changecategory.php",',
						'data: {changecategory:changecategory},',
						'success: function() {',
						'},',
						'error: function() {',
						'alert("Error getting php file");',
						'}',
					'});',
				'});',
					'</script>';
			//******************************************************************************//
			
			
			echo '</br>';
			echo 'Grade: <input type="text" style="width:50px;" name="grade">';
			echo '<input type="submit" name="ginsert" value="Submit"/>';
			echo '</form>';
			echo '</div>';
			}
			
			//**********************NaN check script*********************************//
			echo '<script>';
			echo 'function validate(){',
					' var x = document.forms["grade_form"]["grade"].value;',
					' if (isNaN(x) || x == ""){',
					' 	alert("Not a number");', 
					' 	return false}',
					' else return true;}',
					'</script>';
			//*****************************************************************************//
			
			//**********************insert grade/change category POST***************************************//
			if(isset($_POST['ginsert'])){
				$grade = $_POST['grade'];
				$username = $_POST['my_dropdown'];
				$newcategory = $_POST['my_categorydropdown'];
				$newcategoryid = qa_db_query_raw('SELECT categoryid FROM qa_categories WHERE title = "'.$newcategory.'"');
				$newcategoryid = mysql_fetch_assoc($newcategoryid);
				
				
				$userid = qa_db_query_raw("SELECT userid from qa_users WHERE handle = '".$username."'");
				$row = mysql_fetch_assoc($userid);
				
				//if grade exists, Delete --> Insert new Grade
				qa_db_query_raw("DELETE FROM qa_grades WHERE userid = '".$row["userid"]."' AND postid = '".$postid."'");
				qa_db_query_raw("INSERT INTO qa_grades (userid,grade,postid,categoryid)
								VALUES ('".$row["userid"]."','".$grade."','".$postid."','".$post_info['categoryid']."')");
				
				//Change Category
				$changecategory = $_SESSION['changecategory'];
				if($changecategory == "true"){
					qa_post_set_category($postid, $newcategoryid['categoryid']);
					qa_db_query_raw("UPDATE qa_grades
									SET categoryid = '".$newcategoryid['categoryid']."'
									WHERE postid = '".$postid."'
									");
				}
			}
			//****************************************************************************//
			
		}
	}
	
	//default option for plugin
	function option_default($option)
	{
		//default permissions
		//SEE qa-app-options for selection of permissions
		if ($option=='plugin_grade_mstr_permit_edit') {
			require_once QA_INCLUDE_DIR.'qa-app-options.php';
			return QA_PERMIT_MODERATORS;
		}
		return null;
	}

	//Tset up options on the php plugin
	function admin_form(&$qa_content)
	{
	
		require_once QA_INCLUDE_DIR.'qa-app-admin.php';
		require_once QA_INCLUDE_DIR.'qa-app-options.php';

		$permitoptions=qa_admin_permit_options(QA_PERMIT_USERS, QA_PERMIT_SUPERS, false, false);
	
		$saved=false;
		
		//click to save
		if (qa_clicked('plugin_grade_mstr_save_button')) {
			qa_opt('plugin_grade_mstr_permit_edit', (int)qa_post_text('plugin_grade_mstr_pe_field'));
			$saved=true;
		}
		
		//array of options
		return array(
			'ok' => $saved ? 'Grade Master settings saved' : null,
			
			'fields' => array(
				
				array(
					//permit options
					'label' => 'Allow grading:',
					'type' => 'select',
					'value' => @$permitoptions[qa_opt('plugin_grade_mstr_permit_edit')],
					'options' => $permitoptions,
					'tags' => 'NAME="plugin_grade_mstr_pe_field"',
				),
			),
			
			'buttons' => array(
				array(
					'label' => 'Save Changes',
					'tags' => 'NAME="plugin_grade_mstr_save_button"',
				),
			),
		);
	}	
	
	
}

//*******************dropdown function***************************//
function dropdown( $name, array $options, $selected=null,$postid)
{
    /*** begin the select ***/
	if($postid!=false){
    $dropdown = '<select name="'.$name.'" id="'.$name.'" onchange="updateinfo(this.options[this.selectedIndex].value,'.$postid.')">'."\n";
	}
	else
		$dropdown = '<select name="'.$name.'" id="'.$name.'")">'."\n";
	
	
    $selected = $selected;
    /*** loop over the options ***/
    foreach( $options as $key=>$option )
    {
        /*** assign a selected value ***/
        $select = $selected==$key ? ' selected' : null;

        /*** add each option to the dropdown ***/
        $dropdown .= '<option value="'.$option.'"'.$select.'>'.$option.'</option>'."\n";
    }

    /*** close the select ***/
    $dropdown .= '</select>'."\n";

    /*** and return the completed dropdown ***/
    return $dropdown;
}
//*****************************************************************************//

	


?>
