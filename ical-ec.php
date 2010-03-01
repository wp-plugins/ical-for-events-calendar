<?php
/*
Plugin Name: iCal for Events Calendar
Plugin URI: http://wordpress.org/extend/plugins/ical-for-events-calendar/
Description: Creates an iCal feed for Events Calendar (http://www.lukehowell.com/events-calendar) at http://your-web-address/?ical. Based on Gary King's iCal Posts (http://www.kinggary.com/archives/build-an-ical-feed-from-your-wordpress-posts-plugin) and modifications by Jerome (http://capacity.electronest.com/ical-for-ec-event-calendar/).
Version: 1.1.1
Author: Mark Inderhees
Author URI: http://mark.inderhees.net

---------------------------------------------------------------------
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You can see a copy of GPL at <http://www.gnu.org/licenses/>
---------------------------------------------------------------------
*/

include_once(dirname(__FILE__) . "/ical-ec-admin.php");

function iCalFeed()
{
	global $wpdb;

	if (isset($_GET["debug"]))
	{
		define("DEBUG", true);
	}

	$queryEvents = "SELECT id, eventTitle, eventDescription, eventLocation, ";
	$queryEvents .= "eventLinkout, eventStartDate, eventStartTime, ";
	$queryEvents .= "eventEndDate, eventEndTime ";
	$queryEvents .= "FROM wp_eventscalendar_main ";
	$queryEvents .= "WHERE accessLevel='public' AND id > 0 ";
	if (!is_null(get_option("ical-ec-history-length-months")))
	{
		$historyLengthMonths = get_option("ical-ec-history-length-months");
		$limitDate = strtotime("-" . $historyLengthMonths . " month");
		$limitDate = date("Y-m-d", $limitDate);
		$queryEvents .= "AND eventStartDate >= '" . $limitDate . "' ";
	}
	$queryEvents .= "ORDER BY eventStartDate DESC";

	$posts = $wpdb->get_results($queryEvents);

	$events = "";
	$space = "    ";
	foreach ($posts as $post)
	{
		$convertDateStart = explode("-", $post->eventStartDate);
		$convertDateEnd = explode("-", $post->eventEndDate);

		if (NULL != $post->eventStartTime)
		{
			$convertHoursStart = explode(":", $post->eventStartTime);
		}
		else
		{
			$convertHoursStart = explode(":", "20:00:00");
		}

		if (NULL != $post->eventEndTime)
		{
			$convertHoursEnd = explode(":", $post->eventEndTime);
		}
		else
		{
			$convertHoursEnd = explode(":", "20:00:00");
		}

		$convertedStart = mktime(
			$convertHoursStart[0] - get_option("gmt_offset"),   //hours
			$convertHoursStart[1],                              //minutes
			$convertHoursStart[2],                              //seconds
			$convertDateStart[1],                               //month
			$convertDateStart[2],                               //day
			$convertDateStart[0]                                //year
		);

		$convertedEnd = mktime(
			$convertHoursEnd[0] - get_option("gmt_offset"),     //hours
			$convertHoursEnd[1],                                //minutes
			$convertHoursEnd[2],                                //seconds
			$convertDateEnd[1],                                 //month
			$convertDateEnd[2],                                 //day
			$convertDateEnd[0]                                  //year
		);

		$eventStart = date("Ymd\THis", $convertedStart) . "Z";
		$eventEnd = date("Ymd\THis", $convertedEnd) . "Z";
		$summary = $post->eventTitle;
		$description = $post->eventDescription;
		$description = str_replace(",", "\,", $description);
		$description = str_replace("\\", "\\\\", $description);
		$description = str_replace("\n", $space, strip_tags($description));
		$description = str_replace("\r", $space, strip_tags($description));
		$location = $post->eventLocation;
		$link = $post->eventLinkout;

		$uid = $post->id . "@" . get_bloginfo('home');

		$events .= "BEGIN:VEVENT\r\n";
		$events .= "UID:" . $uid . "\r\n";
		$events .= "DTSTART:" . $eventStart . "\r\n";
		$events .= "DTEND:" . $eventEnd . "\r\n";
		$events .= "LOCATION:" . $location . "\r\n";
		$events .= "SUMMARY:" . $summary . "\r\n";
		if (is_null($link))
		{
			// no link out
			$events .= "DESCRIPTION:" . $description . "\r\n";
		}
		else
		{
			// has link out
			$events .= "DESCRIPTION;ALTREP=\"" . $link . "\":";
			$events .= $description . "\r\n";
		}
		$events .= "END:VEVENT\r\n";
		$events .= "\r\n";
	}

	$blogName = get_bloginfo('name');
	$blogURL = get_bloginfo('home');

	if (!defined('DEBUG'))
	{
		header('Content-Type: text/calendar; charset=UTF-8');
		header('Content-Disposition: attachment; filename="iCal-EC.ics"');
	}

	$content = "BEGIN:VCALENDAR\r\n";
	$content .= "VERSION:2.0\r\n";
	$content .= "PRODID:-//" . $blogName . "//NONSGML v1.0//EN\r\n";
	$content .= "X-WR-CALNAME:" . $blogName . "\r\n";
	$content .= "X-ORIGINAL-URL:" . $blogURL . "\r\n";
	$content .= "X-WR-CALDESC:Events for " . $blogName . "\r\n";
	$content .= "CALSCALE:GREGORIAN\r\n";
	$content .= "METHOD:PUBLISH\r\n";
	$content .= $events;
	$content .= "END:VCALENDAR";

	echo $content;

	if (defined('DEBUG'))
	{
		echo "\n" . $queryEvents . "\n";	
		echo $eventStart . "\n";
	}

	exit;
}

add_action("admin_menu", "ical_ec_option_menu_init");
add_filter(
	"plugin_action_links_" . plugin_basename(__FILE__),
	"ical_ec_action_links"); 
if (isset($_GET['ical']))
{
	add_action('init', 'iCalFeed');
}

?>