<?php
	
/*Create database table
TABLE NAME STRING: qa_grades
postid INT: id of the question that is being graded
userid INT: id of the user who is receiving the grade
grade FLOAT: grade the user received
*/

class qa_grade_master_database{
	
	function init_queries($tableslc){
		
		$tablename='qa_grades'; 
		if(!in_array($tablename, $tableslc)) { 

			return'CREATE TABLE IF NOT EXISTS `'.$tablename.'` ( 
			`postid` int(10), 
			`userid` int(10), 
			`categoryid` int(10),
			`grade` FLOAT(10)
			)'; 
			
		}
		
}
	
}