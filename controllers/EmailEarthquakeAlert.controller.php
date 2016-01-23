<?php

class EmailEarthquakeAlert extends SendEmail
{
	public static function send($address, $subject, $message)
	{
		return parent::send($address, $subject, $message);
	}
}