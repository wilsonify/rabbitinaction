#!/usr/bin/env php
<?php

### JJWW: Straight forward. But is there a way to make so that you can configure the queue/exchange
###         bindings without changing the code? Make it more realistic as a starting point for 
###         distributed logging? Or maybe this grows into that throughout the example?


require_once('../amqp.inc');
include_once('./default_amqp_conf.php');

$exchange = 'logs_exchange';

$callback_function = isset($argv[1]) ? $argv[1] : 'screen_logger';
$consumer_tag = isset($argv[2]) ? $argv[2] : 'consumer';

$conn = new AMQPConnection(HOST, PORT, USER, PASS);
$ch = $conn->channel();
$ch->access_request("/", false, false, true, true);

list($queue) = $ch->queue_declare();

echo $queue, "\n";

$ch->exchange_declare($exchange, 'fanout', false, true, false);
$ch->queue_bind($queue, $exchange);

$file_logger = function($msg) use ($ch, $consumer_tag){
  $cmd = sprintf('echo "%s" >> /tmp/%s.log' , $msg->body, $consumer_tag);
  echo "Executing command: ", $cmd, "\n";
  exec($cmd); # JJWW: Is there a way to do this with native calls? Just think adding a shell call might be confusing as to how it's operating.
  $ch->basic_ack($msg->delivery_info['delivery_tag']);
};

$screen_logger = function($msg) use ($ch, $consumer_tag){
  echo $msg->body, "\n";
  $ch->basic_ack($msg->delivery_info['delivery_tag']);
};

$shutdown = function($ch, $conn) use ($consumer_tag){
  $ch->basic_cancel($consumer_tag);
  $ch->close();
  $conn->close();
};

register_shutdown_function($shutdown, $ch, $conn);

$ch->basic_consume($queue, $consumer_tag, false, false, false, false, $$callback_function);

while(count($ch->callbacks)) {
    $ch->wait();
}
?>