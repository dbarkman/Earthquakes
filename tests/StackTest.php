<?php

/**
 * Created by PhpStorm.
 * User: David
 * Date: 2/16/16
 * Time: 12:58 PM
 */

class StackTest extends PHPUnit_Framework_TestCase
{
	public function testPushAndPop()
	{
		$stack = array();
		$this->assertEquals(0, count($stack));

		array_push($stack, 'foo');
		$this->assertEquals('foo', $stack[count($stack)-1]);
		$this->assertEquals(1, count($stack));

		$this->assertEquals('foo', array_pop($stack));
		$this->assertEquals(0, count($stack));
	}
}
