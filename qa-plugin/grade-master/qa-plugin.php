<?php

/*
	Plugin Name: Grade Master
	Plugin URI: 
	Plugin Description: Allows grades to be assigned to questions/answers
	Plugin Version: 1.0
	Plugin Date: 2013-10-28
	Plugin Author:
	Plugin Author URI:
	Plugin License: GPLv2
	Plugin Minimum Question2Answer Version: 1.5
	Plugin Update Check URI: 
*/

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}

//Import file for Grade Widget
qa_register_plugin_module(
	'widget', // type of module
	'qa-grade-mstr-widget.php', // PHP file containing module class
	'qa_grade_master_widget', // module class name in that PHP file
	'Grade Master' // human-readable name of module
);

//Import file for Initializing the Grades Database
qa_register_plugin_module(
	'module', // type of module
	'qa-grade-mstr-db.php', // PHP file containing module class
	'qa_grade_master_database', // module class name in that PHP file
	'Database Load' // human-readable name of module
);

//Import file for the Grades Page
qa_register_plugin_module('page','qa-grade-mstr-userpg.php','qa_grade_master_usergrades_page','User Grades Page');
