<?php

/**
 * PostMark.controller.php
 * Description:
 *
 */

class MailGun extends Curl
{

	private $_logger;
	private $_from;
	private $_to;
	private $_subject;
	private $_text;
	private $_fields;
	private $_fieldsArray;

	const URL = 'https://api.mailgun.net/v2/mg.everyearthquake.com/messages';
	const APIKEY = 'api:key-55bbff228ddaae8c11a0b72aec605faf';

	public function __construct($logger) {
		parent::__construct($logger);

		$this->_logger = $logger;
	}

	public function sendEmail()
	{
		self::preparePostFields();
		$url = self::URL;

		return self::runCurl('POST', $url, null, self::APIKEY, $this->_fieldsArray);
	}

	private function preparePostFields()
	{
		$this->_fieldsArray = array(
			'from' => $this->_from,
			'to' => $this->_to,
			'subject' => $this->_subject,
			'text' => $this->_text
		);
		$count = 0;
		foreach($this->_fieldsArray as $key => $value) {
			if ($count > 0) $this->_fields .= '&';
			$this->_fields .= $key . '=' . $value;
			$count++;
		}
	}

//	protected function runCurl($requestMethod, $url, $fields, $userpwd)
//	{
//		return parent::runCurl($requestMethod, $url, $fields, $userpwd);
//	}

	/**
	 * @return mixed
	 */
	public function getFrom()
	{
		return $this->_from;
	}

	/**
	 * @param mixed $from
	 */
	public function setFrom($from)
	{
		$this->_from = $from;
	}

	/**
	 * @return mixed
	 */
	public function getTo()
	{
		return $this->_to;
	}

	/**
	 * @param mixed $to
	 */
	public function setTo($to)
	{
		$this->_to = $to;
	}

	/**
	 * @return mixed
	 */
	public function getSubject()
	{
		return $this->_subject;
	}

	/**
	 * @param mixed $subject
	 */
	public function setSubject($subject)
	{
		$this->_subject = $subject;
	}

	/**
	 * @return mixed
	 */
	public function getText()
	{
		return $this->_text;
	}

	/**
	 * @param mixed $text
	 */
	public function setText($text)
	{
		$this->_text = $text;
	}
}
