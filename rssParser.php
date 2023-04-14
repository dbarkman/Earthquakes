<?php

/**
 * rssParser.php
 * Project: Earthquakes
 * Created with PhpStorm
 * Developer: David Barkman
 * Created on: 3/21/23 @ 22:15
 */

session_start();

require_once dirname(__FILE__) . '/includes/includes.php';

$container = new Container();
$logger = $container->getLogger();

$blogFeed = new BlogFeed("https://www.upwork.com/ab/feed/jobs/rss?q=%28app+OR+development+OR+mobile%29+AND+iOS+AND+NOT+%28react+OR+flutter+OR+flutterflow+OR+ionic%29&sort=recency&user_location_match=1&paging=0%3B10&api_params=1&securityToken=aef91c7ca75f8801ddc4c8d325965d529f89b2d67b71fbc70540b0c832a1a2c98af436c0a50ee538812256529c30e4fbc92ea5dd7a7caf8224bd1517668a7c98&userUid=652608874966384640&orgUid=652608874970578945");

global $emails;
$davidEmail = $emails['davidbarkman'];
$davidMobileEmail = $emails['davidMobile'];
$updateBotEmail = $emails['upworkBot'];

foreach ($blogFeed->posts as $post) {
    if (strpos(file_get_contents("ts"), " " . $post->ts . " ") === false) {
        file_put_contents("ts", " " . $post->ts . " " . PHP_EOL, FILE_APPEND);
        $sendMail = new SendEmail($logger);
        $sendMail->send($updateBotEmail, $davidEmail, "New Job on UpWork!", PHP_EOL . $post->title . PHP_EOL . $post->date . PHP_EOL . html_entity_decode(htmlentities($post->summary)) . PHP_EOL . $post->link);
        $sendMail->send($updateBotEmail, $davidMobileEmail, "", PHP_EOL . $post->title . PHP_EOL . $post->date);
    }
}

class BlogPost {
    var $date;
    var $ts;
    var $link;
    var $title;
    var $text;
    var $summary;
}

class BlogFeed {
    var $posts = array();

    function __construct($file_or_url) {

        $file_or_url = $this->resolveFile($file_or_url);
        if (!($x = simplexml_load_file($file_or_url)))
            return;

        foreach ($x->channel->item as $item) {
            $post = new BlogPost();
            $post->date = (string)$item->pubDate;
            $post->ts = strtotime($item->pubDate);
            $post->link = (string)$item->link;
            $post->title = (string)$item->title;
            $post->text = (string)$item->description;

            // Create summary as a shortened body and remove images,
            // extraneous line breaks, etc.
            $post->summary = $this->summarizeText($post->text);

            $this->posts[] = $post;
        }
    }

    private function resolveFile($file_or_url) {
        if (!preg_match('|^https?:|', $file_or_url))
            $feed_uri = $_SERVER['DOCUMENT_ROOT'] . '/shared/xml/' . $file_or_url;
        else
            $feed_uri = $file_or_url;

        return $feed_uri;
    }

    private function summarizeText($summary) {
        $summary = strip_tags($summary);

        // Truncate summary line to 100 characters
        $max_len = 100;
        if (strlen($summary) > $max_len)
            $summary = substr($summary, 0, $max_len) . '...';

        return $summary;
    }
}
