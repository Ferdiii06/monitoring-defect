<?php

namespace Database\Seeders;

use App\Models\KanbanComponent;
use Illuminate\Database\Seeder;

class KanbanComponentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Default sequence: 1=assy_number, 2=conveyor, 3=serial, delimiter='.'
     */
    public function run(): void
    {
        $defaults = [
            [
                'component_key' => KanbanComponent::KEY_ASSY_NUMBER,
                'label'         => 'Nomor Assy',
                'sequence'      => 1,
                'delimiter'     => '.',
            ],
            [
                'component_key' => KanbanComponent::KEY_CONVEYOR,
                'label'         => 'Konveyor',
                'sequence'      => 2,
                'delimiter'     => '.',
            ],
            [
                'component_key' => KanbanComponent::KEY_SERIAL,
                'label'         => 'Serial Number',
                'sequence'      => 3,
                'delimiter'     => '.',
            ],
        ];

        foreach ($defaults as $item) {
            KanbanComponent::updateOrCreate(
                ['component_key' => $item['component_key']],
                [
                    'label'     => $item['label'],
                    'sequence'  => $item['sequence'],
                    'delimiter' => $item['delimiter'],
                ]
            );
        }
    }
}

