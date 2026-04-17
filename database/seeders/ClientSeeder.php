<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Client;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $clients = [
            [
                'id'        => 'kuba',
                'name'      => 'Kuba',
                'trainer_id'=> 'maciek',
                'email'     => null,
                'phone'     => null,
                'password'  => 'kuba123',
                'status'    => 'active',
            ],
        ];

        foreach ($clients as $data) {
            Client::firstOrCreate(
                ['id' => $data['id']],
                [
                    'name'          => $data['name'],
                    'trainer_id'    => $data['trainer_id'],
                    'email'         => $data['email'],
                    'phone'         => $data['phone'],
                    'password_hash' => Hash::make($data['password']),
                    'status'        => $data['status'],
                ]
            );
        }
    }
}
