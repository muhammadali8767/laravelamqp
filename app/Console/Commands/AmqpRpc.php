<?php

namespace App\Console\Commands;

use Bschmitt\Amqp\Facades\Amqp;
use Illuminate\Console\Command;

class AmqpRpc extends Command
{
    protected $signature = 'amqp:rpc';
    protected $description = 'Command description';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        Amqp::consume(
            'videos',
            function ($message, $resolver) {
                var_dump($message->body);
                sleep(20);
                $resolver->acknowledge($message);
            },
            [
                'message_limit' => 1,
                'exchange' => 'shard.videos',
                'exchange_type' => 'x-modulus-hash',
                'routing_key' => 'fc-analyze',
            ]
        );
    }
}
