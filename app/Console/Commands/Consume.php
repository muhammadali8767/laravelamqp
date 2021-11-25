<?php

namespace App\Console\Commands;

use Bschmitt\Amqp\Consumer;
use Bschmitt\Amqp\Facades\Amqp;
use Illuminate\Console\Command;
use PhpAmqpLib\Message\AMQPMessage;

class Consume extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'consume';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Amqp::consume('xt.request', function (AMQPMessage $message, Consumer $consumer) {

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
        }, [
            'exchange' => 'common',
            'exchange_type' => 'topic',
            'queue_force_declare' => true,
            'queue_durable' => false,
            'consumer_no_ack' => true,
        ]);
    }
}
