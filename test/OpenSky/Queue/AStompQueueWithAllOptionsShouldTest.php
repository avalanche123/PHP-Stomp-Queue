<?php

namespace OpenSky\Queue;

/* 
 * This file is part of The OpenSky Project
 */

/**
 * Description of AStompQueueWithAllOptionsShouldTest
 *
 * @author Bulat Shakirzyanov <bulat@theopenskyproject.com>
 */
class AStompQueueWithAllOptionsShouldTest extends \PHPUnit_Framework_TestCase {

	private $options = array(
		'username'	=> 'system',
		'password'	=> 'manager',
		'host'		=> 'localhost',
		'scheme'	=> 'tcp',
		'port'		=> '61613'
	);

	private $queue;

	public function setUp() {
		$this->queue = new StompAdapter($this->options);
	}

	public function tearDown() {
		$this->queue = null;
	}

	public function testHaveAllOptions() {
		$this->assertEquals($this->options['username'], $this->queue->getUsername());
		$this->assertEquals($this->options['password'], $this->queue->getPassword());
		$this->assertEquals($this->options['host'], $this->queue->getHost());
		$this->assertEquals($this->options['scheme'], $this->queue->getScheme());
		$this->assertEquals($this->options['port'], $this->queue->getPort());
	}

}
