<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\Workspace;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DemoWorkspaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $workspace = Workspace::firstOrCreate(
            ['name' => 'Demo Workspace']
        );

        $facility = Facility::firstOrCreate(
            ['workspace_id' => $workspace->id, 'name' => 'Demo Facility'],
        );

        $this->command->info("Workspace [{$workspace->id}] Demo Workspace ready.");
        $this->command->info("Facility [{$facility->id}] Demo Facility ready.");
    }
}
