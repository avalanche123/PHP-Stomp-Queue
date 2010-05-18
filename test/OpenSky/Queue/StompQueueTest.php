<?php

namespace OpenSky\Queue;

/* 
 * This file is part of The OpenSky Project
 */

/**
 * Description of StompQueueTest
 *
 * @author Bulat Shakirzyanov <bulat@theopenskyproject.com>
 */
class StompQueueTest extends \PHPUnit_Framework_TestCase {

    protected $_queue;

    public function tearDown() {
        $this->_queue = null;
    }

    public function setUp() {

        $this->_queue = $this->getQueueMock();
    }

    protected function getQueueMock($command = 'CONNECTED') {
        $queue = new StompAdapter(array());
        $frameClass = $queue->getStompClientFrameClass();
        $stompClient = $this->getStompClient($frameClass, $command);
        $queue->setStompClient($stompClient);
        return $queue;
    }

    protected function getStompClient($frameClass, $command = 'CONNECTED',
        $connectCount = false
    ) {
        $stompClient = $this->getMock('Zend_Queue_Stomp_Client', array(
            'send', 'receive', 'getConnection'
        ));
        if (false === $connectCount) {
            $expects = $this->any();
        } else {
            $expects = $this->exactly((int) $connectCount);
        }
        $connectFrame = new $frameClass;
        $connectFrame->setCommand($command);
        $message = new $frameClass;
        $message->setCommand('MESSAGE');
        $message->setHeader('message-id', 'message-1');
        $message->setBody('some message body');
        $message2 = new $frameClass;
        $message2->setCommand('MESSAGE');
        $message2->setHeader('message-id', 'message-2');
        $message2->setBody('another message body');
        $stompClient->expects($expects)
            ->method('send')
            ->will($this->returnValue($stompClient));
        $stompClient->expects($this->any())
            ->method('receive')
            ->will($this->onConsecutiveCalls($connectFrame, $message, $message2));
        $stompClient->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($this->getStompConnection()));
        return $stompClient;
    }

    protected function getStompConnection() {
        $connection = $this->getMock('Zend_Queue_Stomp_Client_Connection', array(
            'open'
        ));

        return $connection;
    }


    public function testSetsIsConnectedToTrueOnSuccessfulConnect() {
        $queue = $this->_queue;
        $queue->connect();
        $this->assertTrue($queue->isConnected());
    }

    public function testSetsIsConnectedToFalseAndThrowsExceptionOnUnsuccessfulConnect() {
        $queue = $this->getQueueMock('DENIED');
        try {
            $queue->connect();
        } catch (\Zend_Queue_Exception $e) {}
        $this->assertFalse($queue->isConnected());
    }


    public function testShouldCreateCorrectConnectFrame() {
        $aQueue = $this->_queue;
        $frame = $aQueue->getConnectFrame();
        $this->assertEquals('CONNECT', $frame->getCommand());
    }

    public function createCorrectSendFrame($messageBody, \Zend_Queue $queueFacade) {
        $queue = $this->_queue;
        $frame = $queue->getSendFrame($messageBody, $queueFacade);
        $this->assertEquals($messageBody, $frame->getBody());
        $this->assertEquals(strlen($messageBody), $frame->getHeader('content-length'));
        $this->assertEquals($queueFacade->getName(), $frame->getHeader('destination'));
        $this->assertEquals('SEND', $frame->getCommand());

        unset ($frame);

        $frame = $queue->getSendFrame($messageBody, $queueFacade, array('OPENSKY_MESSAGE_LIMIT' => 1));
        $this->assertEquals('1', $frame->getHeader('OPENSKY_MESSAGE_LIMIT'));

        return $frame;
    }

    /**
     * @dataProvider getQueueAndMessage
     */
    public function testGetCorrectMessageFromSendFrame($messageBody, $queueName) {
        $queue = $this->_queue;
        $queueFacade = new \Zend_Queue(array('name' => $queueName));
        $frame = $this->createCorrectSendFrame($messageBody, $queueFacade);
        $message = $queue->getMessageFromFrame($frame, $queueFacade);
        $messageClass = $queueFacade->getMessageClass();
        $this->assertTrue($message instanceof $messageClass);
        $this->assertEquals($queueFacade, $message->getQueue());
        $this->assertEquals($messageBody, $message->body);
    }

    public function getQueueAndMessage() {
        return array(
            array('some_topic', 'asdasdasdasdasda asdasd asdqweas asdasdqwea asdqwe asd')
        );
    }

    /**
     * @dataProvider getQueueAndMessage
     */
    public function testSendsMessage($messageBody, $queueName) {
        $queue = new \Zend_Queue(array('name' => $queueName));
        $message = $this->_queue->send($messageBody, $queue);
        $this->assertEquals($queue, $message->getQueue());
        $this->assertEquals($messageBody, $message->body);
    }

    /**
     * @dataProvider getQueueAndMessage
     */
    public function testConnectsIfNotConnectedOnSend($messageBody, $queueName) {
        $this->assertFalse($this->_queue->isConnected());
        $queue = new \Zend_Queue(array('name' => $queueName));
        $message = $this->_queue->send($messageBody, $queue);
        $this->assertTrue($this->_queue->isConnected());
    }

    public function testShouldNotConnectMoreThatOnceIfAlreadyConnected() {
        $stompClient = $this->getStompClient($this->_queue->getStompClientFrameClass(), 'CONNECTED', 1);
        $this->_queue->setStompClient($stompClient);
        $this->assertFalse($this->_queue->isConnected());
        $this->_queue->connect();
        $this->assertTrue($this->_queue->isConnected());
        $this->_queue->connect();
    }

    public function testShouldNotDisconnectIfNotConnected() {
        $stompClient = $this->getStompClient($this->_queue->getStompClientFrameClass(), 'CONNECTED', 0);
        $this->_queue->setStompClient($stompClient);
        $this->assertFalse($this->_queue->isConnected());
        $this->_queue->disconnect();
    }

    public function testShouldDisconnectIfConnected() {
        $stompClient = $this->getStompClient($this->_queue->getStompClientFrameClass(), 'CONNECTED', 2);
        $this->_queue->setStompClient($stompClient);
        $this->assertFalse($this->_queue->isConnected());
        $this->_queue->connect();
        $this->assertTrue($this->_queue->isConnected());
        $this->_queue->disconnect();
        $this->assertFalse($this->_queue->isConnected());
        $this->_queue = null;
    }

    public function testShouldCreateCorrectDisconnectFrame() {
        $frame = $this->_queue->getDisconnectFrame();
        $this->assertEquals('DISCONNECT', $frame->getCommand());
    }

    public function testShouldNotWaitLongerThanTimeoutSpecified() {
        $queueName = 'some_queue';
        $destination = new \Zend_Queue(array('name' => $queueName));
        $expected = 0.00005;
        $start = microtime(true);
        $messages = $this->_queue->receive(5, $expected, $destination);
        $elapsed = microtime(true) - $start;
        if (count($messages) == 2) {
            $this->fail('should not spend more time on receiving messages than specified');
        }
    }

    public function testShouldReceiveMessagesFromClientOnReceive() {
        $stompClient = $this->getStompClient($this->_queue->getStompClientFrameClass(), 'CONNECTED', 3);
        $this->_queue->setStompClient($stompClient);

        $queueName = 'some_queue';
        $destination = new \Zend_Queue(array('name' => $queueName));

        $this->assertFalse($this->_queue->isConnected());
        $messages = $this->_queue->receive(2, null, $destination);
        $this->assertTrue($this->_queue->isConnected());

        $messageSetClass = $destination->getMessageSetClass();
        $this->assertTrue($messages instanceof $messageSetClass);
        $this->assertEquals(2, count($messages));
        $ids = array('message-1', 'message-2');
        $bodys = array('some message body', 'another message body');
        $i = 0;
        foreach($messages as $message) {
            $this->assertEquals($ids[$i], $message->message_id);
            $this->assertEquals($bodys[$i], $message->body);
            $i++;
        }
        unset($this->_queue);
    }

    public function testShouldCreateCorrectSubscribeFrame() {
        $queueName = 'some_queue';
        $destination = new \Zend_Queue(array('name' => $queueName));
        $frame = $this->_queue->getSubscribeFrame($destination);

        $this->assertEquals($queueName, $frame->getHeader('destination'));
        $this->assertEquals('client', $frame->getHeader('ack'));
        $this->assertEquals('true', $frame->getHeader('no-local'));
        $this->assertEquals('SUBSCRIBE', $frame->getCommand());

        unset ($frame);

        $frame = $this->_queue->getSubscribeFrame($destination, array('OPENSKY_MESSAGE_LIMIT' => '1'));
        $this->assertEquals('1', $frame->getHeader('OPENSKY_MESSAGE_LIMIT'));
    }

    public function testShouldCreateCorrectDeleteFrame() {
        $queueName = 'some_queue';
        $destination = new \Zend_Queue(array('name' => $queueName));
        $messages = $this->_queue->receive(1, null, $destination);
        $message = $messages->current();

        $frame = $this->_queue->getDeleteFrame($message);

        $this->assertEquals('ACK', $frame->getCommand());
        $this->assertEquals($message->message_id, $frame->getHeader('message-id'));
    }

    public function testShouldDeleteMessagesFromQueue() {
        $queueName = 'some_queue';
        $destination = new \Zend_Queue(array('name' => $queueName));
        $messages = $this->_queue->receive(1, null, $destination);
        $message = $messages->current();
        $this->assertTrue($this->_queue->deleteMessage($message));
    }

    public function testShouldReceiveOneMessageByDefault() {
        $queueName = 'some_queue';
        $destination = new \Zend_Queue(array('name' => $queueName));
        $this->_queue->setQueue($destination);
        $messages = $this->_queue->receive();
        $this->assertEquals(1, count($messages));
    }
}
