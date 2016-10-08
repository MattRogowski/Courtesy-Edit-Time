<?php
/**
 * Courtesy Edit Time 1.1.0

 * Copyright 2016 Matthew Rogowski

 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at

 ** http://www.apache.org/licenses/LICENSE-2.0

 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
**/

if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("postbit", "courtesyedittime_postbit");
$plugins->add_hook("xmlhttp", "courtesyedittime_xmlhttp");

function courtesyedittime_info()
{
	return array(
		"name" => "Courtesy Edit Time",
		"description" => "Allow a courtesy edit time, whereby the 'edited by' message won't show up for a set amount of time.",
		"website" => "https://github.com/MattRogowski/Courtesy-Edit-Time",
		"author" => "Matt Rogowski",
		"authorsite" => "https://matt.rogow.ski",
		"version" => "1.1.0",
		"compatibility" => "16*,18*",
		"codename" => "courtesyedittime"
	);
}

function courtesyedittime_activate()
{
	global $db;
	
	courtesyedittime_deactivate();
	
	$settings_group = array(
		"name" => "courtesyedittime",
		"title" => "Courtesy Edit Time Settings",
		"description" => "Settings for the courtesy edit time plugin.",
		"disporder" => "28",
		"isdefault" => 0
	);
	$db->insert_query("settinggroups", $settings_group);
	$gid = $db->insert_id();
	
	$settings = array();
	$settings[] = array(
		"name" => "courtesyedittime",
		"title" => "Courtesy Edit Time",
		"description" => "Enter the number of seconds that the 'edited by' message will not show for. As an example, if you put 60 in the box, users will be able to edit their posts for 60 seconds before the 'edited by' message will be added.",
		"optionscode" => "text",
		"value" => "60"
	);
	$i = 1;
	foreach($settings as $setting)
	{
		$insert = array(
			"name" => $db->escape_string($setting['name']),
			"title" => $db->escape_string($setting['title']),
			"description" => $db->escape_string($setting['description']),
			"optionscode" => $db->escape_string($setting['optionscode']),
			"value" => $db->escape_string($setting['value']),
			"disporder" => intval($i),
			"gid" => intval($gid),
		);
		$db->insert_query("settings", $insert);
		$i++;
	}
	
	rebuild_settings();
}

function courtesyedittime_deactivate()
{
	global $db;
	
	$db->delete_query("settinggroups", "name = 'courtesyedittime'");
	
	$settings = array(
		"courtesyedittime"
	);
	$settings = "'" . implode("','", $settings) . "'";
	$db->delete_query("settings", "name IN ({$settings})");
	
	rebuild_settings();
}

function courtesyedittime_postbit(&$post)
{
	if(courtesyedittime_hide_message($post, $post['edittime']))
	{
		$post['editedmsg'] = "";
	}
}

function courtesyedittime_xmlhttp()
{
	global $mybb, $plugins;
	
	if($mybb->input['action'] == "edit_post")
	{
		$plugins->add_hook("datahandler_post_update", "courtesyedittime_do_xmlhttp", 20);
	}
}

function courtesyedittime_do_xmlhttp(&$data)
{
	global $mybb;

	$post = get_post($data->pid);
	if(courtesyedittime_hide_message($post, TIME_NOW))
	{
		$mybb->settings['showeditedby'] = 0;
	}
}

function courtesyedittime_hide_message($post, $edittime)
{
	global $mybb;

	if($mybb->settings['courtesyedittime'] > 0 && ($post['dateline'] + $mybb->settings['courtesyedittime']) > $edittime)
	{
		return true;
	}

	return false;
}
?>