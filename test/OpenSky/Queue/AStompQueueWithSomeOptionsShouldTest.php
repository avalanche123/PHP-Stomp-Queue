<?php

namespace OpenSky\Queue;

/* 
 * This file is part of The OpenSky Project
 */

/**
 * Description of AStompQueueWithSomeOptionsShouldTest
 *
 * @author Bulat Shakirzyanov <bulat@theopenskyproject.com>
 */
class AStompQueueWithSomeOptionsShouldTest extends \PHPUnit_Framework_TestCase {

    public function testReturnNullForNonExistentOptions() {
        $options = array(
            'host'        => 'localhost',
            'scheme'    => 'tcp',
            'port'        => '61613'
        );
        $aQueue = new StompAdapter($options);

        $this->assertNull($aQueue->getUsername());
        $this->assertNull($aQueue->getPassword());
        $this->assertEquals($options['host'], $aQueue->getHost());
        $this->assertEquals($options['scheme'], $aQueue->getScheme());
        $this->assertEquals($options['port'], $aQueue->getPort());
    }

}
