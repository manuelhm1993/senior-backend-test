<?php

namespace App\Jobs;

use App\Models\Installation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;

use Illuminate\Support\Str;
use PhpAmqpLib\Message\AMQPMessage;

class SendActivationMessage implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $installationId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $installation = Installation::with('facility.workspace')->find($this->installationId);

        if (!$installation) {
            // Registro desapareció entre el dispatch y la ejecución: no hay
            // nada que activar, se descarta sin reintentar.
            return;
        }

        $exchangeName = env('RABBITMQ_EXCHANGE', 'installations.exchange');
        $routingKey   = $installation->installation_id;

        Log::info('Publishing activation message', [
            'exchange'    => $exchangeName,
            'routing_key' => $routingKey,
            'host'        => env('RABBITMQ_HOST'),
            'vhost'       => env('RABBITMQ_VHOST'),
        ]);

        $body = [
            'message_id'       => (string) Str::uuid(),
            'correlation_id'   => (string) Str::uuid(),
            'contract_version' => '1.0',
            'type'             => 'activation',
            'payload'          => [
                'workspace' => $installation->facility?->workspace?->name,
                'facility'  => $installation->facility?->name,
                'owner'     => [
                    'name'  => $installation->owner_name,
                    'email' => $installation->owner_email,
                ],
                'devices' => [], // lista inicial de instrumentos, vacía en este alcance de demo
            ],
        ];

        $connection = new AMQPStreamConnection(
            env('RABBITMQ_HOST', 'rabbitmq'),
            env('RABBITMQ_PORT', 5672),
            env('RABBITMQ_USER', 'guest'),
            env('RABBITMQ_PASSWORD', 'guest'),
            env('RABBITMQ_VHOST', '/')
        );

        $channel = $connection->channel();

        $message = new AMQPMessage(
            json_encode($body),
            [
                'content_type'  => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ]
        );

        $channel->basic_publish($message, $exchangeName, $routingKey);

        $channel->close();
        $connection->close();

        $installation->update(['contract_version' => '1.0']);
    }
}
