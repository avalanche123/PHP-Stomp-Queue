# Usage #
Pretty standard:

	$queueAdapter = new \OpenSky\Queue\Stomp(array(
		'host'		=> 'localhost',
		'scheme'	=> 'tcp',
		'port'		=> 61613,
		'username'	=> 'system', //optional
		'password'	=> 'manager', //optional
	));

	$queue = new \Zend_Queue($queueAdapter, array(
		'name' => '/queue/some_queue'
	));

	$queue->send('someMessageString');

-------------------------------------------------------

	$messagesToReceiveCount = 1;
	$messages = $queue->receive($messagesToReceiveCount);
	$this->assertEquals(1, count($messages);

	$message = $messages->current();
	$this->assertEquals('someMessageString', $message->body);
