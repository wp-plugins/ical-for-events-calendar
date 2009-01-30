<?php
/*
Plugin Name: iCal for Events Calendar
Plugin URI: http://wordpress.org/extend/plugins/ical-for-events-calendar/
Description: Creates an iCal feed for Events Calendar (http://www.lukehowell.com/events-calendar) at http://your-web-address/?ical. Based on Gary King's iCal Posts (http://www.kinggary.com/archives/build-an-ical-feed-from-your-wordpress-posts-plugin) and modifications by Jerome (http://capacity.electronest.com/ical-for-ec-event-calendar/).
Version: 1.0
Author: Mark Inderhees
Author URI: http://mark.inderhees.net
*/

function iCalFeed()
{
    global $wpdb;

    if (isset($_GET["debug"]))
    {
        define("DEBUG", true);
    }

    $queryEvents = "SELECT id, eventTitle, eventDescription, eventLocation, ";
    $queryEvents .= "eventStartDate, eventStartTime, eventEndDate, ";
    $queryEvents .= "eventEndTime ";
    $queryEvents .= "FROM wp_eventscalendar_main ";
    $queryEvents .= "WHERE accessLevel='public' AND id > 0 ";
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

        $uid = $post->id . "@" . get_bloginfo('home');

        $events .= "BEGIN:VEVENT\n";
        $events .= "UID:" . $uid . "\n";
        $events .= "DTSTART:" . $eventStart . "\n";
        $events .= "DTEND:" . $eventEnd . "\n";
        $events .= "SUMMARY:" . $summary . "\n";
        $events .= "DESCRIPTION:" . $description . "\n";
        $events .= "END:VEVENT\n";
    }

    $blogName = get_bloginfo('name');
    $blogURL = get_bloginfo('home');

    if (!defined('DEBUG'))
    {
        header('Content-type: text/calendar');
        header('Content-Disposition: attachment; filename="iCal-EC.ics"');
    }

    $content = "BEGIN:VCALENDAR\n";
    $content .= "VERSION:2.0\n";
    $content .= "PRODID:-//" . $blogName . "//NONSGML v1.0//EN\n";
    $content .= "X-WR-CALNAME:" . $blogName . "\n";
    $content .= "X-ORIGINAL-URL:" . $blogURL . "\n";
    $content .= "X-WR-CALDESC:Events for " . $blogName . "\n";
    $content .= "CALSCALE:GREGORIAN\n";
    $content .= "METHOD:PUBLISH\n";
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

if (isset($_GET['ical']))
{
    add_action('init', 'iCalFeed');
}

?>
