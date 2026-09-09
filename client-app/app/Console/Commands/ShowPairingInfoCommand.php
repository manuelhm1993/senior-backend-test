<?php

namespace App\Console\Commands;

use App\Models\Installation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use Illuminate\Support\Str;

#[Signature('client:pair-info')]
#[Description('Displays or generates the local installation pairing credentials')]
class ShowPairingInfoCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $installation = Installation::first();

        if (!$installation) {
            $installation = Installation::create([
                'installation_id' => (string) Str::ulid(),
                'pairing_secret'  => Str::random(40),
                'status'          => 'unpaired',
            ]);

            $this->info('New installation identity generated successfully.');
        }

        $this->newLine();
        $this->table(
            ['Property', 'Value'],
            [
                ['Public Installation ID (ULID)', $installation->installation_id],
                ['Pairing Secret Token', $installation->pairing_secret],
                ['Current Status', $installation->status],
            ]
        );
        $this->newLine();

        return self::SUCCESS;
    }
}
