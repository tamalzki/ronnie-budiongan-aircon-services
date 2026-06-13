<?php

use App\Models\Service;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $names = [
            'Aircon Cleaning – Split Type',
            'Aircon Cleaning – Window Type',
            'Chemical Cleaning',
            'Chemical Overhaul',
            'Freon Refilling / Refrigerant Charging',
            'Aircon Installation',
            'Aircon Relocation / Transfer',
            'Outdoor Transfer',
            'Indoor Transfer',
            'Aircon Repair & Troubleshooting',
            'Preventive Maintenance Service (PMS)',
            'Water Leak Repair',
            'Outdoor Unit Cleaning',
            'Dismantling / Pull-Out Service',
        ];

        foreach ($names as $name) {
            Service::firstOrCreate(['name' => $name], [
                'default_price' => 0,
                'is_active' => true,
            ]);
        }
    }

    public function down(): void
    {
        //
    }
};
