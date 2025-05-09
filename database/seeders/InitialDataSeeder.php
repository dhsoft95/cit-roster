<?php

namespace Database\Seeders;

use App\Models\PermanentAssignment;
use App\Models\Personnel;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitialDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'david@gmail.com'],
            [
                'name' => 'David',
                'email' => 'david@gmail.com',
                'password' => Hash::make('password123'), // Change the password as needed
            ]
        );
        // Sample data from the provided table
        $data = [
            [
                'registration' => 'T 225 EHU',
                'driver' => 'SACK',
                'car_commander' => 'ANDERSON',
                'crew' => 'SIL',
            ],
            [
                'registration' => 'T244 EHU',
                'driver' => 'EMANUEL',
                'car_commander' => 'SIMON',
                'crew' => 'ENTER',
            ],
            [
                'registration' => 'T 117 DXQ',
                'driver' => 'MWAMBOSHO',
                'car_commander' => 'JACOB',
                'crew' => 'DRAGE',
            ],
            [
                'registration' => 'T 987 DXQ',
                'driver' => 'PETER',
                'car_commander' => 'GRACE',
                'crew' => 'GRADE',
            ],
            [
                'registration' => 'T 288 DXR',
                'driver' => 'SAID',
                'car_commander' => 'ALBERT',
                'crew' => 'REST',
            ],
            [
                'registration' => 'T 991 DXR',
                'driver' => 'PAUL',
                'car_commander' => 'JANE',
                'crew' => 'TROY',
            ],
            [
                'registration' => 'T 993 DXQ',
                'driver' => 'NYANGO',
                'car_commander' => 'EZEKIEL',
                'crew' => 'MADU',
            ],
            [
                'registration' => 'T 287 DXR',
                'driver' => 'DAVIUS',
                'car_commander' => 'MANGO',
                'crew' => 'NONE',
            ],
            [
                'registration' => 'T 285 DXR',
                'driver' => 'FRED',
                'car_commander' => 'GRADYS',
                'crew' => 'GONE',
            ],
            [
                'registration' => 'T 954 DXQ',
                'driver' => 'DANIEL',
                'car_commander' => 'MWAMBA',
                'crew' => 'HOTEL',
            ],
            [
                'registration' => 'T 233 EHU',
                'driver' => 'KAMBONA',
                'car_commander' => 'OMOLLO',
                'crew' => 'KILO',
            ],
            [
                'registration' => 'T 956 DXQ',
                'driver' => 'SEIF',
                'car_commander' => 'ESTHER',
                'crew' => 'YANKEE',
            ],
            [
                'registration' => 'T 228 EHU',
                'driver' => 'EMMA',
                'car_commander' => 'OSED',
                'crew' => 'ZULU',
            ],
            [
                'registration' => 'T 461 DHP',
                'driver' => 'KOKA',
                'car_commander' => 'HAMPREY',
                'crew' => 'SIERRA',
            ],
            [
                'registration' => 'T 116 FRD',
                'driver' => 'DELLY',
                'car_commander' => 'ALF',
                'crew' => 'DAD',
            ],
            [
                'registration' => 'T 235 DER',
                'driver' => 'YAAKIN',
                'car_commander' => 'SEU',
                'crew' => 'UAM',
            ],
            [
                'registration' => 'T 456 HED',
                'driver' => 'SAM',
                'car_commander' => 'BET',
                'crew' => 'TAKE',
            ],
        ];

        // Create all vehicles first
        foreach ($data as $item) {
            Vehicle::create([
                'registration_number' => $item['registration'],
                'status' => 'active',
            ]);
        }

        // Create all personnel from data
        $drivers = [];
        $commanders = [];
        $crews = [];

        foreach ($data as $item) {
            // Create driver if not exists
            if (!in_array($item['driver'], $drivers)) {
                Personnel::create([
                    'name' => $item['driver'],
                    'role' => 'driver',
                    'status' => 'active',
                ]);
                $drivers[] = $item['driver'];
            }

            // Create car commander if not exists
            if (!in_array($item['car_commander'], $commanders)) {
                Personnel::create([
                    'name' => $item['car_commander'],
                    'role' => 'car_commander',
                    'status' => 'active',
                ]);
                $commanders[] = $item['car_commander'];
            }

            // Create crew if not exists
            if (!in_array($item['crew'], $crews)) {
                Personnel::create([
                    'name' => $item['crew'],
                    'role' => 'crew',
                    'status' => 'active',
                ]);
                $crews[] = $item['crew'];
            }
        }

        // Add additional car commanders for rotation
        $additionalCommanders = [
            'WILSON', 'KEVIN', 'THOMAS', 'MARY', 'JOSEPH', 'JULIAN',
            'HASSAN', 'AMINA', 'FATIMA', 'LILIAN', 'GEORGE', 'VICTOR',
            'MICHAEL', 'SOPHIA', 'OLIVIA', 'NOAH', 'EMMA', 'OLIVER',
            'CHARLOTTE', 'ELIZABETH', 'JAMES', 'BENJAMIN', 'LUCAS',
            'HENRY', 'ALEXANDER', 'WILLIAM', 'SOPHIA', 'MIA', 'HARPER'
        ];

        foreach ($additionalCommanders as $name) {
            if (!in_array($name, $commanders)) {
                Personnel::create([
                    'name' => $name,
                    'role' => 'car_commander',
                    'status' => 'active',
                ]);
                $commanders[] = $name;
            }
        }

        // Add additional crew members for rotation
        $additionalCrews = [
            'ALPHA', 'BRAVO', 'CHARLIE', 'DELTA', 'ECHO', 'FOXTROT', 'GOLF',
            'INDIA', 'JULIET', 'LIMA', 'MIKE', 'NOVEMBER', 'OSCAR', 'PAPA',
            'QUEBEC', 'ROMEO', 'TANGO', 'UNIFORM', 'VICTOR', 'WHISKEY', 'XRAY',
            'ROMEO1', 'TANGO1', 'UNIFORM1', 'VICTOR1', 'WHISKEY1', 'XRAY1',
            'ALPHA1', 'BRAVO1', 'CHARLIE1', 'DELTA1', 'ECHO1', 'FOXTROT1'
        ];

        foreach ($additionalCrews as $name) {
            if (!in_array($name, $crews)) {
                Personnel::create([
                    'name' => $name,
                    'role' => 'crew',
                    'status' => 'active',
                ]);
                $crews[] = $name;
            }
        }

        // Create permanent assignments
        foreach ($data as $item) {
            $vehicle = Vehicle::where('registration_number', $item['registration'])->first();
            $driver = Personnel::where('name', $item['driver'])->first();

            PermanentAssignment::create([
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driver->id,
            ]);
        }

        // Output statistics
        $this->command->info('Seeded: ' . count($data) . ' vehicles');
        $this->command->info('Seeded: ' . count($drivers) . ' drivers');
        $this->command->info('Seeded: ' . count($commanders) . ' car commanders');
        $this->command->info('Seeded: ' . count($crews) . ' crew members');
    }
}
