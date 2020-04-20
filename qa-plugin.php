<?php
/*
	Plugin Name: Private Message Adapter
	Plugin URI: https://github.com/JacksiroKe/q2a-pm-adapter
	Plugin Description: Adds an editor of your choice on the private message and feedback pages, including support for HTML messages.
	Plugin Version: 1.2
	Plugin Date: 2018-07-20
	Plugin Author: JacksiroKe
	Plugin Author URI: https://github.com/JacksiroKe
	Plugin License: GPLv3
	Plugin Minimum Question2Answer Version: 1.6
	Plugin Update Check URI: 

*/

if ( !defined('QA_VERSION') )
{
	header('Location: ../../');
	exit;
	
}
	
	qa_register_plugin_module('widget', 'pm-adapter.php', 'pm_adapter', 'Private Message');

/*
	Omit PHP closing tag to help avoid accidental output
*/
