<?php

namespace OpenSky\Queue;

use Zend_Queue,
    Zend_Queue_Message,
    Zend_Queue_Adapter_AdapterAbstract,
    Zend_Queue_Stomp_Client,
    Zend_Queue_Exception;

/* 
 * This file is part of The OpenSky Project
 */

/**
 * Description of StompQueue
 *
 * @author Bulat Shakirzyanov <bulat@theopenskyproject.com>
 */
class StompAdapter extends Zend_Queue_Adapter_AdapterAbstract {

    const
        DEFAULT_HOST                    = 'localhost',
        DEFAULT_SCHEME                    = 'tcp',
        DEFAULT_PORT                    = '61613',
        DEFAULT_STOMP_CLIENT_CLASS        = 'Zend_Queue_Stomp_Client',
        DEFAULT_STOMP_CONNECTION_CLASS    = 'Zend_Queue_Stomp_Client_Connection',
        DEFAULT_STOMP_FRAME_CLASS        = 'Zend_Queue_Stomp_Frame'
        ;

    protected $_options = array(
        'host'                            => self::DEFAULT_HOST,
        'scheme'                        => self::DEFAULT_SCHEME,
        'port'                            => self::DEFAULT_PORT,
        'stompClientClass'                => self::DEFAULT_STOMP_CLIENT_CLASS,
        'stompClientConnectionClass'    => self::DEFAULT_STOMP_CONNECTION_CLASS,
        'stompClientFrameClass'            => self::DEFAULT_STOMP_FRAME_CLASS,
    );

    protected $_stompClient;

    private $_connected = false;

    private function _getOption($name) {
        return isset($this->_options[$name]) ? $this->_options[$name] : null;
    }

    public function getUsername() {
        return $this->_getOption('username');
    }

    public function getPassword() {
        return $this->_getOption('password');
    }

    public function getHost() {
        return $this->_getOption('host');
    }

    public function getPort() {
        return $this->_getOption('port');
    }

    public function getScheme() {
        return $this->_getOption('scheme');
    }

    public function getStompClientClass() {
        return $this->_getOption('stompClientClass');
    }

    public function getStompClientFrameClass() {
        return $this->_getOption('stompClientFrameClass');
    }

    public function getStompClientConnectionClass() {
        return $this->_getOption('stompClientConnectionClass');
    }

    /**
     * @param array $headers
     */
    public function connect() {
        if (!$this->isConnected()) {
            $headers = (4 === func_num_args()) ? (array) func_get_arg(3) : array();
            $client = $this->getStompClient();
            $response = $client->send(
                $this->getConnectFrame($headers)
            )->receive();
            if ((false !== $response)
                && ($response->getCommand() != 'CONNECTED')
            ) {
                throw new Zend_Queue_Exception(
                    "Unable to authenticate to '".
                    $this->getScheme().'://'.$this->getHost().':'.$this->getPort()
                    ."'"
                );
            }
            $this->_connected = true;
        }
        return $this;
    }

    /**
     * @param array $headers
     */
    public function disconnect() {
        if ($this->isConnected()) {
            $client = $this->getStompClient();
            if ($client->getConnection()) {
                $client->send($this->getDisconnectFrame());
            }
            $this->_connected = false;
        }
        return $this;
    }

    public function  __destruct() {
        $this->disconnect();
    }

    /**
     * @param array $headers
     */
    public function getDisconnectFrame(array $headers = array()) {
        $frame = $this->getStompClient()->createFrame();
        $frame->setCommand('DISCONNECT');

        foreach ($headers as $name => $header) {
            $frame->setHeader($name, $header);
        }

        return $frame;
    }

    /**
     * @param array $headers
     */
    public function getConnectFrame(array $headers = array()) {
        $frame = $this->getStompClient()->createFrame();

        // Username and password are optional on some messaging servers
        // such as Apache's ActiveMQ
        $frame->setCommand('CONNECT');
        if (null !== ($username = $this->getUsername())) {
            $frame->setHeader('login', $username);
            $frame->setHeader('passcode', $this->getPassword());
        }

        foreach ($headers as $name => $header) {
            $frame->setHeader($name, $header);
        }

        return $frame;
    }

    /**
     * @param Zend_Queue $queue
     * @param array $headers
     */
    public function getSubscribeFrame(Zend_Queue $queue, array $headers = array()) {
        $frame = $this->getStompClient()->createFrame();
        $frame->setCommand('SUBSCRIBE');
        $frame->setHeader('destination', $queue->getName());
        $frame->setHeader('ack', 'client');
        $frame->setHeader('no-local', 'true');

        foreach ($headers as $name => $header) {
            $frame->setHeader($name, $header);
        }

        return $frame;
    }

    /**
     * @param Zend_Queue_Message $message
     * @param array $headers
     */
    public function getDeleteFrame(Zend_Queue_Message $message, array $headers = array()) {
        $frame = $this->getStompClient()->createFrame();
        $frame->setCommand('ACK');
        $frame->setHeader('message-id', $message->handle);

        foreach ($headers as $name => $header) {
            $frame->setHeader($name, $header);
        }

        return $frame;
    }

    public function getStompClient() {
        if (null !== $this->_stompClient) {
            return $this->_stompClient;
        }
        if (null !== ($clientClass = $this->_getOption('stompClientClass'))) {
            return $this->_stompClient = new $clientClass(
                $this->getScheme(),
                $this->getHost(),
                $this->getPort(),
                $this->getStompClientConnectionClass(),
                $this->getStompClientFrameClass()
            );
        }
        return null;
    }

    public function setStompClient(Zend_Queue_Stomp_Client $client) {
        $this->_stompClient = $client;
        return $this;
    }

    public function isConnected() {
        return $this->_connected;
    }

    /**
     * Does a queue already exist?
     *
     * Use isSupported('isExists') to determine if an adapter can test for
     * queue existance.
     *
     * @param  string $name Queue name
     * @return boolean
     */
    public function isExists($name) {
        throw new Zend_Queue_Exception('isExists() is not supported in this adapter');
    }

    /**
     * Create a new queue
     *
     * Visibility timeout is how long a message is left in the queue
     * "invisible" to other readers.  If the message is acknowleged (deleted)
     * before the timeout, then the message is deleted.  However, if the
     * timeout expires then the message will be made available to other queue
     * readers.
     *
     * @param  string  $name Queue name
     * @param  integer $timeout Default visibility timeout
     * @return boolean
     */
    public function create($name, $timeout=null) {
        throw new Zend_Queue_Exception('create() is not supported in this adapter');
    }

    /**
     * Delete a queue and all of its messages
     *
     * Return false if the queue is not found, true if the queue exists.
     *
     * @param  string $name Queue name
     * @return boolean
     */
    public function delete($name) {
        throw new Zend_Queue_Exception('delete() is not supported in this adapter');
    }

    /**
     * Get an array of all available queues
     *
     * Not all adapters support getQueues(); use isSupported('getQueues')
     * to determine if the adapter supports this feature.
     *
     * @return array
     */
    public function getQueues() {
        throw new Zend_Queue_Exception('getQueues() is not supported in this adapter');
    }

    /**
     * Return the approximate number of messages in the queue
     *
     * @param  Zend_Queue|null $queue
     * @return integer
     */
    public function count(Zend_Queue $queue = null) {
        throw new Zend_Queue_Exception('count() is not supported in this adapter');
    }

    /********************************************************************
     * Messsage management functions
     *********************************************************************/

    /**
     *
     * @param string $message
     * @param Zend_Queue $queue
     * @param array $headers
     * @return Zend_Queue_Stomp_Frame
     */
    public function getSendFrame($message, Zend_Queue $queue, array $headers = array()) {
        $frame = $this->getStompClient()->createFrame();
        $frame->setCommand('SEND');
        $frame->setHeader('destination', $queue->getName());
        $frame->setHeader('content-length', strlen($message));
        $frame->setBody((string) $message);
        foreach ($headers as $name => $header) {
            $frame->setHeader($name, $header);
        }
        return $frame;
    }

    public function getMessageFromFrame(
        \Zend_Queue_Stomp_FrameInterface $frame,
        Zend_Queue $queue
    ) {
        $messageClass = $queue->getMessageClass();
        $message = $frame->getBody();
        return new $messageClass(array(
            'queue'    => $queue,
            'data'    => array(
                'message_id' => $frame->getHeader('message-id') ?: null,
                'body'       => $message,
                'md5'        => md5($message),
                'handle'     => $frame->getHeader('message-id') ?: null
            )
        ));
    }

    /**
     * Send a message to the queue
     *
     * @param  mixed $message Message to send to the active queue
     * @param  Zend_Queue|null $queue
     * @param  array $headers
     * @return Zend_Queue_Message
     */
    public function send($message, Zend_Queue $queue = null) {
        $headers = (4 === func_num_args()) ? (array) func_get_arg(3) : array();
        $this->connect();
        $queue = $queue ?: $this->_queue;
        $frame = $this->getSendFrame($message, $queue, $headers);
        $this->getStompClient()->send($frame);
        $message = $this->getMessageFromFrame($frame, $queue);
        return $message;
    }

    /**
     * Get messages in the queue
     *
     * @param  integer|null $maxMessages Maximum number of messages to return
     * @param  integer|null $timeout Visibility timeout for these messages
     * @param  Zend_Queue|null $queue
     * @param  array $headers
     * @return Zend_Queue_Message_Iterator
     */
    public function receive($maxMessages = null, $timeout = null, Zend_Queue $queue = null) {
        $headers = (4 === func_num_args()) ? (array) func_get_arg(3) : array();
        $this->connect();
        $queue = $queue ?: $this->_queue;
        $timeout = (float) $timeout ?: self::RECEIVE_TIMEOUT_DEFAULT;
        $maxMessages = (int) $maxMessages ?: 1;
        $this->getStompClient()->send($this->getSubscribeFrame($queue, $headers));

        $start = microtime(true);
        $frameClass = $this->getStompClientFrameClass();

        $messages = array();
        for($i = 0; $i < $maxMessages; $i++) {
            if((microtime(true) - $start) >= $timeout) {
                break;
            }
            $messageFrame = $this->getStompClient()->receive();
            if (!$messageFrame || 'MESSAGE' !== $messageFrame->getCommand()) {
                break;
            }
            $message = $this->getMessageFromFrame($messageFrame, $queue);
            $messages[] = $message->toArray();
        }

        $messageSetClass = $queue->getMessageSetClass();
        $messageSet = new $messageSetClass(array(
            'queue' => $queue,
            'messageClass' => $queue->getMessageClass(),
            'data' => $messages,
        ));
        return $messageSet;
    }

    /**
     * Delete a message from the queue
     *
     * Return true if the message is deleted, false if the deletion is
     * unsuccessful.
     *
     * @param  Zend_Queue_Message $message
     * @param  array $headers
     * @return boolean
     */
    public function deleteMessage(Zend_Queue_Message $message) {
        $headers = (4 === func_num_args()) ? (array) func_get_arg(3) : array();
        $this->connect();
        $frame = $this->getDeleteFrame($message, $headers);
        $this->getStompClient()->send($frame);

        return true;
    }

    /********************************************************************
     * Supporting functions
     *********************************************************************/

    /**
     * Return a list of queue capabilities functions
     *
     * $array['function name'] = true or false
     * true is supported, false is not supported.
     *
     * @return array
     */
    public function getCapabilities() {
        return array(
            'create'        => false,
            'delete'        => false,
            'send'          => true,
            'receive'       => true,
            'deleteMessage' => true,
            'getQueues'     => false,
            'count'         => false,
            'isExists'      => false,
        );
    }
}
