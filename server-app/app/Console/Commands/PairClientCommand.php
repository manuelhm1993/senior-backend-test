<?php

namespace App\Console\Commands;

use App\Models\Facility;
use App\Models\Installation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('server:pair-client {ulid} {secret} {facility_id} {--owner-name=Demo Owner} {--owner-email=owner@example.com}')]
#[Description('Registers a Client installation and associates it with a Facility')]
class PairClientCommand extends Command
{
    public function handle(): int
    {
        $ulid       = $this->argument('ulid');
        $secret     = $this->argument('secret');
        $facilityId = $this->argument('facility_id');
        $ownerName  = $this->option('owner-name');
        $ownerEmail = $this->option('owner-email');

        $facility = Facility::find($facilityId);

        if (!$facility) {
            $this->error("Facility [{$facilityId}] not found.");
            return self::FAILURE;
        }

        $installation = Installation::where('installation_id', $ulid)->first();

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
                'facility_id'         => $facility->id,
                'pairing_secret_hash' => bcrypt($secret),
                'status'              => 'pending_activation',
                'owner_name'          => $ownerName,
                'owner_email'         => $ownerEmail,
            ]
        );

        \App\Jobs\SendActivationMessage::dispatch($installation->id);

        $this->info("Installation [{$ulid}] associated with facility [{$facility->name}].");
        $this->info('Status: pending_activation — activation job dispatched.');

        return self::SUCCESS;
    }
}
