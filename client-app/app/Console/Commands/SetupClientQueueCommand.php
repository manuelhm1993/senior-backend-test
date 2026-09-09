<?php

namespace App\Console\Commands;

use App\Models\Installation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;

#[Signature('client:setup-queue')]
#[Description('Declares the RabbitMQ exchange, queue and binding for this installation ULID')]
class SetupClientQueueCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $installation = Installation::first();

        if (!$installation) {
            $this->error('No installation found. Run "php artisan client:pair-info" first.');
            return self::FAILURE;
        }

        $exchangeName = env('RABBITMQ_EXCHANGE', 'installations.exchange');
        $queueName    = env('RABBITMQ_QUEUE', 'client_queue');
        $routingKey   = $installation->installation_id;

        $connection = new AMQPStreamConnection(
            env('RABBITMQ_HOST', 'rabbitmq'),
            env('RABBITMQ_PORT', 5672),
            env('RABBITMQ_USER', 'guest'),
            env('RABBITMQ_PASSWORD', 'guest'),
            env('RABBITMQ_VHOST', '/')
        );

        $channel = $connection->channel();

        // Direct exchange: enruta por routing key exacta, no por broadcast.
        $channel->exchange_declare(
            $exchangeName,
            AMQPExchangeType::DIRECT,
            false, // passive
            true,  // durable
            false  // auto_delete
        );

        // Cola fija: un solo Client en este demo, el aislamiento lo da el binding, no el nombre.
        $channel->queue_declare(
            $queueName,
            false, // passive
            true,  // durable
            false, // exclusive
            false  // auto_delete
        );

        // El binding es lo único que depende del ULID: solo mensajes con esta routing key llegan a esta cola.
        $channel->queue_bind($queueName, $exchangeName, $routingKey);

        $channel->close();
        $connection->close();

        $this->info("Queue [{$queueName}] bound to exchange [{$exchangeName}] with routing key [{$routingKey}].");

        return self::SUCCESS;
    }
}
