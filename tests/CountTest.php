<?php
/**
 * Created by PhpStorm.
 * User: David
 * Date: 2/16/16
 * Time: 10:28 PM
 */

class CountTest extends PHPUnit_Framework_TestCase
{
	public function testPushAndPop()
	{
		$count = 0;
		$this->assertEquals(0, $count);

		$count++;
		$this->assertEquals(1, $count);

		$count = $count + 1;
		$this->assertEquals(2, $count);

		$count += 1;
		$this->assertEquals(3, $count);
		$this->assertEquals(4, $count);
	}
}
