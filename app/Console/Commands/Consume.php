<?php

namespace App\Console\Commands;

use Bschmitt\Amqp\Consumer;
use Illuminate\Console\Command;
use PhpAmqpLib\Message\AMQPMessage;

class Consume extends Command
{
    protected $signature = 'amqp:consume';
    protected $description = 'Command description';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        \Amqp::consume('worker', function (AMQPMessage $message, Consumer $consumer) {
            $this->info($message->getBody(), 'v');

            $correlationId = $message->has('correlation_id') ? $message->get('correlation_id') : null;
            $consumer->getChannel()->basic_publish(
                new AMQPMessage(
                    $response,
                    [
                        'content_type' => 'text/plain',
                        'delivery_mode' => 1,
                        'correlation_id' => $correlationId
                    ]
                ),
                '',
                $message->get('reply_to')
            );
        }, [
            'exchange' => 'amq.direct',
            'exchange_type' => 'direct',
            'queue_force_declare' => true,
            'queue_durable' => false,
            'consumer_no_ack' => true,
        ]);

        \Amqp::consume(
            'xt.request',
            function (AMQPMessage $message, Consumer $consumer) {

                var_dump($message->getBody());
                var_dump($message->get('reply_to'));

                $response = 'pong';
                var_dump($response);

                $correlationId = $message->has('correlation_id') ? $message->get('correlation_id') : null;
                $consumer->getChannel()->basic_publish(
                    new AMQPMessage(
                        $response,
                        [
                            'content_type' => 'text/plain',
                            'delivery_mode' => 1,
                            'correlation_id' => $correlationId
                        ]
                    ),
                    '',
                    $message->get('reply_to')
                );
            },
            [
                'routing' => 'xt.request',
                'exchange' => 'test.topic',
                // 'exchange' => 'common',
                'exchange_type' => 'topic',
                'queue_force_declare' => true,
                'queue_durable' => false,
                'consumer_no_ack' => true,
                'persistent' => true // required if you want to listen forever
            ]
        );
    }
}
