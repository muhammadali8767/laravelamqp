<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Bschmitt\Amqp\Publisher;

class Commit extends Command
{
    protected $signature = 'amqp:commit';
    protected $description = 'Command description';
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $message  = json_encode([
            "ETP_ID" => 4,
            "REQUEST_ID" => 555111,
            "METHOD_NAME" => "PING",
            "PAYLOAD" => [
                "data" => "some_string"
            ]
        ]);

        $response = $this->queue_rpc('xt.request', $message, 10); // -> pong
        var_dump($response->getBody());
    }

    public function queue_rpc($queue, $message, $timeout = 0)
    {
        /* @var Bschmitt\Amqp\Publisher $publisher */
        $publisher = app()->make('Bschmitt\Amqp\Publisher');
        $publisher->connect();
        $publisher->getConnection()->set_close_on_destruct();
        $replyTo = $publisher->getChannel()->queue_declare(
            'xt.responce',
            false,
            false,
            true,
            true
        );
        $replyTo = $replyTo[0];
        $publisher->getChannel()->queue_declare(
            $queue,
            false,
            false,
            false,
            false
        );
        $response = false;
        $publisher->getChannel()->basic_consume(
            $replyTo,
            '',
            false,
            false,
            false,
            false,
            function ($message) use (&$response) {
                $response = $message;
            }
        );
        $publisher->getChannel()->queue_bind($queue, 'common', $queue);
        $publisher->getChannel()->basic_publish(
            new \Bschmitt\Amqp\Message(
                $message,
                [
                    'routing_key' => $queue,
                    'content_type' => 'text/plain',
                    'delivery_mode' => 2,
                    'reply_to' => $replyTo,
                ]
            ),
            'common',
            $queue
        );
        $publisher->getChannel()->wait(null, false, $timeout);
        return $response;
    }
}
