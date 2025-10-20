<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Participant;
use App\Models\Election;
use App\Models\SubConsite;
use Illuminate\Support\Str;

class ParticipantSeeder extends Seeder
{
    public function run(): void
    {
        $election = Election::where('name','Male City Council')->first();
        if(!$election){ return; }

        // Fetch ALL active sub consites
        $subConsiteIds = SubConsite::where('status','Active')->pluck('id');
        $count = 0;
        foreach($subConsiteIds as $sid){
            Participant::firstOrCreate(
                ['election_id' => $election->id, 'sub_consite_id' => $sid],
                ['id' => (string) Str::uuid(), 'status' => 'Active']
            );
            $count++;
        }
        $this->command?->info("ParticipantSeeder: seeded/ensured $count participants for election '{$election->name}'.");
    }
}
