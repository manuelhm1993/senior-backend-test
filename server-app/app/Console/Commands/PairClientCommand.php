<?php

namespace App\Console\Commands;

use App\Models\Facility;
use App\Models\Installation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('server:pair-client {ulid} {secret} {facility_id}')]
#[Description('Registers a Client installation and associates it with a Facility')]
class PairClientCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $ulid       = $this->argument('ulid');
        $secret     = $this->argument('secret');
        $facilityId = $this->argument('facility_id');

        $facility = Facility::find($facilityId);

        if (!$facility) {
            $this->error("Facility [{$facilityId}] not found.");
            return self::FAILURE;
        }

        $installation = Installation::where('installation_id', $ulid)->first();

        // Prevención de asociaciones inconsistentes/duplicadas (brief 3.2.5):
        // una instalación ya activa o en proceso no puede re-asociarse silenciosamente.
        if ($installation && in_array($installation->status, ['active', 'pending_activation'])) {
            $this->error(
                "Installation [{$ulid}] is already [{$installation->status}]".
                ($installation->facility_id ? " (facility_id={$installation->facility_id})." : '.')
            );
            $this->line('If you need to re-pair it, unpair it explicitly first.');
            return self::FAILURE;
        }

        $installation = Installation::updateOrCreate(
            ['installation_id' => $ulid],
            [
                'facility_id'          => $facility->id,
                'pairing_secret_hash'  => bcrypt($secret),
                'status'               => 'pending_activation',
            ]
        );

        $this->info("Installation [{$ulid}] associated with facility [{$facility->name}].");
        $this->info('Status: pending_activation — run the activation job next.');

        return self::SUCCESS;
    }
}
