<?php

namespace OpenSky\Queue;

/* 
 * This file is part of The OpenSky Project
 */

/**
 * Description of ANewStompQueueShouldTest
 *
 * @author Bulat Shakirzyanov <bulat@theopenskyproject.com>
 */
class ANewStompQueueShouldTest extends \PHPUnit_Framework_TestCase {

    protected $queue;

    public function setUp() {
        $this->queue = new StompAdapter(array());
    }

    public function tearDown() {
        $this->queue = null;
    }

    public function testHaveDefaultsSpecified() {
        $defaults = array(
            'host'                => 'localhost',
            'scheme'            => 'tcp',
            'port'                => '61613',
            'stompClientClass'    => 'Zend_Queue_Stomp_Client'
        );
        $this->assertEquals($defaults['host'], $this->queue->getHost());
        $this->assertEquals($defaults['scheme'], $this->queue->getScheme());
        $this->assertEquals($defaults['port'], $this->queue->getPort());
        $this->assertEquals($defaults['stompClientClass'], $this->queue->getStompClientClass());
    }

    public function testNotBeConnected() {
        $this->assertFalse($this->queue->isConnected());
    }

    public function testHaveCorrectCapabilities() {
        $capabilities = array(
            'create'        => false,
            'delete'        => false,
            'send'          => true,
            'receive'       => true,
            'deleteMessage' => true,
            'getQueues'     => false,
            'count'         => false,
            'isExists'      => false,
        );

        $this->assertEquals($capabilities, $this->queue->getCapabilities());
    }

    public function testNotSupportCreate() {
        try {
            $this->queue->create('somequeue', 5);
            $this->fail('adapter should not support create()');
        } catch (\Zend_Queue_Exception $e) {}
    }

    public function testNotSupportDelete() {
        try {
            $this->queue->delete('somequeue');
            $this->fail('adapter should not support delete()');
        } catch (\Zend_Queue_Exception $e) {}
    }

    public function testNotSupportGetQueues() {
        try {
            $this->queue->getQueues();
            $this->fail('adapter should not support getQueues()');
        } catch (\Zend_Queue_Exception $e) {}
    }

    public function testNotSupportCount() {
        try {
            $this->queue->count();
            $this->fail('adapter should not support count()');
        } catch (\Zend_Queue_Exception $e) {}
    }

    public function testNotSupportIsExists() {
        try {
            $this->queue->isExists('somequeue');
            $this->fail('adapter should not support isExists()');
        } catch (\Zend_Queue_Exception $e) {}
    }

}
