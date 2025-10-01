<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AccountSeeder extends Seeder
{

    public function run(): void
    {
        Account::updateOrCreate(
            ['email_pengguna' => 'admin@gmail.com'],
            [
                'nama_pengguna' => 'Admin WMS',
                'password'      => Hash::make('admin123'),
                'chain_link'    => 'CHAIN-001',
                'role'          => 'admin',
            ]
        );
    }
}
