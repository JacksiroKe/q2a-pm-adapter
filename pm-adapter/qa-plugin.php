<?php
/*
	Plugin Name: Private Message Adapter
	Plugin URI: https://www.github.com/jacksiro/Q2A-PM-Adapter-Plugin
	Plugin Description: Adds an editor of your choice on the private message and feedback pages, including support for HTML messages.
	Plugin Version: 1.0
	Plugin Date: 2018-07-20
	Plugin Author: Jackson Siro
	Plugin Author URI: https://www.github.com/jacksiro
	Plugin License: GPLv3
	Plugin Minimum Question2Answer Version: 1.6
	Plugin Update Check URI: https://www.github.com/jacksiro/Q2A-PM-Adapter-Plugin/master/pm-adapter/qa-plugin.php

*/

if ( !defined('QA_VERSION') )
{
	header('Location: ../../');
	exit;
	
}

	$plugin_dir = dirname( __FILE__ ) . '/';
	$plugin_url = qa_path_to_root().'qa-plugin/pm-adapter';
	define( "QA_MESSANGER_DIR",  $plugin_url.'/'  );
	
	qa_register_plugin_phrases('pm-lang-*.php', 'pm_lang');
	qa_register_plugin_layer('pm-adapter.php', 'Private Message Layer');
	qa_register_plugin_module('page', 'pm-message.php', 'pm_message', 'Private Message');

	function pm_optionfield_make_select(&$optionfield, $options, $value, $default)
	{
		$optionfield['type'] = 'select';
		$optionfield['options'] = $options;
		$optionfield['value'] = isset($options[qa_html($value)]) ? $options[qa_html($value)] : @$options[$default];
	}
		
	function data_arr_str($datastr)
	{
		if (empty($datastr)) return;
		return (strlen(@$datastr['prefix']) ? $datastr['prefix'] : '') .
			(strlen(@$datastr['data']) ? $datastr['data'] : '') .
			(strlen(@$datastr['suffix']) ? $datastr['suffix'] : '');
	}
	
	function time_formatter($timestamp, $fulldatedays, $finaltime = '')
	{
		$interval = qa_opt('db_time') - $timestamp;
		if ($interval < 0 || (isset($fulldatedays) && $interval > 86400 * $fulldatedays)) {
			$stampyear = date('Y', $timestamp);
			$thisyear = date('Y', qa_opt('db_time'));

			$dateFormat = qa_lang($stampyear == $thisyear ? 'main/date_format_this_year' : 'main/date_format_other_years');
			$replaceData = array(
				'^day' => date(qa_lang('main/date_day_min_digits') == 2 ? 'd' : 'j', $timestamp),
				'^month' => qa_lang('main/date_month_' . date('n', $timestamp)),
				'^year' => date(qa_lang('main/date_year_digits') == 2 ? 'y' : 'Y', $timestamp),
			);
			return qa_html(strtr($dateFormat, $replaceData));
		} else return qa_lang_html_sub_split('main/x_ago', qa_html(qa_time_to_string($interval)));
	}
	
	function pm_sort_by(&$array, $bystr)
	{
		global $qa_sort_by_str;
		$qa_sort_by_str = $bystr;
		uasort($array, 'pm_sort_by_fn');
	}

	function pm_sort_by_fn($a, $b)
	{
		global $qa_sort_by_str;
		$sortkey = $qa_sort_by_str;
		$av = $a[$sortkey];
		$bv = $b[$sortkey];
		if (is_numeric($av) && is_numeric($bv)) 
			return $av == $bv ? 0 : ($bv < $av ? -1 : 1);
		else return strcasecmp($bv, $av);
	}
	
/*
	Omit PHP closing tag to help avoid accidental output
*/
