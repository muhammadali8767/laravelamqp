<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Commit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commit';

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
        $message  = json_encode([
            "request_id" => "1",
            "method_name" => "CLAIM_INFO_ETP",
            "payload" => [
                "data" => "some_string",
            ],
        ]);

        $response = $this->queue_rpc('xt.request', $message);
        var_dump($response->getBody());
    }

    public function queue_rpc($queue, $message, $timeout = 0) {
        /* @var Bschmitt\Amqp\Publisher $publisher */
        $publisher = app()->make('Bschmitt\Amqp\Publisher');
        $publisher->connect();
        $publisher->getConnection()->set_close_on_destruct();
        list($replyTo, ,) = $publisher->getChannel()->queue_declare('', false, false, true, true);
        $replyTo = "xt_in";
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
                    'request_id' => 1,
                    'method_name' => 'ping',
                    'reply_to' => $replyTo,
                    'payload' => '{"data": "some_string"}',
                ]
            ),
            'common',
            $queue
        );
        $publisher->getChannel()->wait(null, false, $timeout);
        return $response;
    }
}
