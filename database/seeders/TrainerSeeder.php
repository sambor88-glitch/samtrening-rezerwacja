<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Trainer;

class TrainerSeeder extends Seeder
{
    public function run(): void
    {
        $trainers = [
            [
                'id'         => 'kasia',
                'name'       => 'Kasia',
                'name_short' => 'K',
                'password'   => 'kasia123',
                'role'       => 'trainer',
                'color'      => '#e91e8c',
                'gradient'   => 'linear-gradient(135deg, #e91e8c, #ff6b9d)',
            ],
            [
                'id'         => 'maciek',
                'name'       => 'Maciek',
                'name_short' => 'M',
                'password'   => 'maciek123',
                'role'       => 'trainer',
                'color'      => '#1976d2',
                'gradient'   => 'linear-gradient(135deg, #1976d2, #42a5f5)',
            ],
            [
                'id'         => 'kuba',
                'name'       => 'Kuba',
                'name_short' => 'K',
                'password'   => 'kuba123',
                'role'       => 'trainer',
                'color'      => '#388e3c',
                'gradient'   => 'linear-gradient(135deg, #388e3c, #66bb6a)',
            ],
        ];

        foreach ($trainers as $data) {
            Trainer::firstOrCreate(
                ['id' => $data['id']],
                [
                    'name'          => $data['name'],
                    'name_short'    => $data['name_short'],
                    'password_hash' => Hash::make($data['password']),
                    'role'          => $data['role'],
                    'color'         => $data['color'],
                    'gradient'      => $data['gradient'],
                ]
            );
        }
    }
}
