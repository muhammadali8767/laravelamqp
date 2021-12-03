<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

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
            "REQUEST_ID" => 1,
            "METHOD_NAME" => "QUERY_ORGAN",
            "PAYLOAD" => [
                "INN" => "231456987"
            ]
        ]);
        $queue = 'xt_in';
        $exchange = 'common';
        $routingKey = 'xt.request';
        $replyTo = 'xt_in';
        $timeout = 0;

        $response = $this->queue_rpc(
            $queue,
            $exchange,
            $routingKey,
            $replyTo,
            $message,
            $timeout
        );

        var_dump($response->getBody());
    }

    private function queue_rpc(
        $queue,
        $exchange,
        $routingKey,
        $replyTo,
        $message,
        $timeout = 0
    ) {
        /* @var Bschmitt\Amqp\Publisher $publisher */
        $publisher = app()->make('Bschmitt\Amqp\Publisher');
        $publisher->connect();
        $publisher->getConnection()->set_close_on_destruct();
        $publisher->getChannel()->queue_declare(
            $queue,
            false,
            false,
            false,
            false
        );

        $response = false;
        $correlationId = uniqid();

        $publisher->getChannel()->basic_consume(
            $replyTo,
            '',
            false,
            false,
            false,
            false,
            function ($message) use (&$response, &$correlationId) {
                if ($message->get('correlation_id') == $correlationId) {
                    $response = $message;
                }
            }
        );

        $publisher->getChannel()->queue_bind(
            $queue,
            $exchange,
            $routingKey
        );

        $publisher->getChannel()->basic_publish(
            new \Bschmitt\Amqp\Message(
                $message,
                [
                    'user_id' => 'xt',
                    'routing_key' => $routingKey,
                    'content_type' => 'application/json',
                    'delivery_mode' => 2,
                    'correlation_id' => $correlationId,
                    'reply_to' => $replyTo,
                ]
            ),
            $exchange,
            $routingKey
        );
        $publisher->getChannel()->wait(null, false, $timeout);
        return $response;
    }
}
