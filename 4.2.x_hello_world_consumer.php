#!/usr/bin/env php
<?php
###################################################
# RabbitMQ in Action
# Chapter 4.2.x - Hello World Consumer
# 
# Requires: php-amqp http://github.com/bkw/php-amqp
# 
# Author: Alvaro Videla
# (C)2010
###################################################

require_once('../amqp.inc');

define('HOST', 'localhost');
define('PORT', 5672);
define('USER', 'guest');
define('PASS', 'guest');

$exchange = 'hello-exchange';
$queue = 'hello-queue';
$consumer_tag = 'consumer';

$conn = new AMQPConnection(HOST, PORT, USER, PASS);
$ch = $conn->channel();

$ch->access_request("/", false, false, true, true);

$ch->exchange_declare($exchange, 'direct', false, true, false);

$ch->queue_declare($queue);

$ch->queue_bind($queue, $exchange);

$consumer = function($msg) use ($ch, $consumer_tag){ # JJWW: What does the 'use' do?

  if ($msg->body === 'quit') { # JJWW: Do you think the cancel logic might be confusing in a Hello World?
      $ch->basic_cancel($consumer_tag);
  } else{
    echo 'Hello ',  $msg->body, "\n";
    $ch->basic_ack($msg->delivery_info['delivery_tag']);
  }
};

$ch->basic_consume($queue, $consumer_tag, false, false, false, false, $consumer);

while(count($ch->callbacks)) {
    $ch->wait();
}

$ch->close();
$conn->close();

?>