<?php

session_start();

require_once dirname(__FILE__) . '/includes/includes.php';

$gf = new getDTFollowers();
$count = $gf->getFollowers();
$entry = date(c) . ',' . $count . PHP_EOL;

$dtFollowersFile = 'dtFolowers.txt';
file_put_contents($dtFollowersFile, $entry, FILE_APPEND | LOCK_EX);

class getDTFollowers
{
	private $_logger;
	private $_container;
	private $_twitter;
	public $verifiedUsers = array();
	public $unVerifiedUsers = array();

	public function __construct()
	{
		$this->_container = new Container();

		$this->_logger = $this->_container->getLogger();

		global $twitterCreds;
		$this->_twitter = new Twitter($twitterCreds['consumerKey'], $twitterCreds['consumerSecret'], $twitterCreds['accessToken'], $twitterCreds['accessTokenSecret']);
	}

	public function getFollowers()
	{
		$params = array(
			'screen_name' => 'realDonaldTrump'
		);

		$response = $this->_twitter->getUser($params);
		$responseDecoded = json_decode($response, true);
		if (isset($responseDecoded['errors'])) {
			echo $responseDecoded['errors'][0]['message'] . PHP_EOL;
			return '0,0,0';
		} else {
		    $followers_count = $responseDecoded['followers_count'];
		    $friends_count = $responseDecoded['friends_count'];
		    $listed_count = $responseDecoded['listed_count'];
		    return $followers_count . ',' . $listed_count . ',' . $friends_count;
		}
	}
}