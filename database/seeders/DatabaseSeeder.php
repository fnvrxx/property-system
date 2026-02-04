<?php

namespace Database\Seeders;

use App\Models\Lease;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Buat User Admin (Agar bisa login)
        User::factory()->create([
            'name' => 'Admin Properti',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('admin123'), // Password: password
            'email_verified_at' => now(),
            // 'is_admin' => true, // Uncomment jika Anda sudah pakai kolom is_admin
        ]);

        // 2. Buat Data Dummy PROPERTI
        // Kita buat variasi Kos, Ruko, dan Tanah
        $properties = [
            ['name' => 'Kamar 101 (AC)', 'type' => 'kos', 'base_price' => 1500000, 'address' => 'Lantai 1'],
            ['name' => 'Kamar 102 (Non-AC)', 'type' => 'kos', 'base_price' => 800000, 'address' => 'Lantai 1'],
            ['name' => 'Kamar 201 (VIP)', 'type' => 'kos', 'base_price' => 2000000, 'address' => 'Lantai 2'],
            ['name' => 'Ruko Blok A1', 'type' => 'bangunan', 'base_price' => 25000000, 'address' => 'Jl. Raya Depan'],
            ['name' => 'Tanah Kavling X', 'type' => 'tanah', 'base_price' => 5000000, 'address' => 'Desa Sebelah'],
        ];

        foreach ($properties as $prop) {
            Property::create($prop);
        }

        // 3. Buat Data Dummy PENYEWA
        $tenants = [
            ['name' => 'Budi Santoso', 'phone_number' => '081234567890', 'identity_number' => '3301001'],
            ['name' => 'Siti Aminah', 'phone_number' => '089876543210', 'identity_number' => '3301002'],
            ['name' => 'Joko Anwar', 'phone_number' => '085678901234', 'identity_number' => '3301003'],
            ['name' => 'Rina Nose', 'phone_number' => '081122334455', 'identity_number' => '3301004'],
        ];

        foreach ($tenants as $tenant) {
            Tenant::create($tenant);
        }

        // 4. Buat Data TRANSAKSI (Skenario Testing Dashboard)

        // KASUS 1: LUNAS (Kamar 101 - Budi)
        // Bayar penuh, jatuh tempo masih lama
        Lease::create([
            'property_id' => 1,
            'tenant_id' => 1,
            'start_date' => Carbon::now()->subMonths(3),
            'payment_frequency' => 'bulanan',
            'price' => 1500000,
            'last_payment_date' => Carbon::now()->subDays(2), // Baru bayar 2 hari lalu
            'amount_paid' => 1500000, // LUNAS
        ]);

        // KASUS 2: KURANG BAYAR (Kamar 102 - Siti)
        // Tagihan 800rb, baru bayar 500rb
        Lease::create([
            'property_id' => 2,
            'tenant_id' => 2,
            'start_date' => Carbon::now()->subMonths(1),
            'payment_frequency' => 'bulanan',
            'price' => 800000,
            'last_payment_date' => Carbon::now()->subDays(10),
            'amount_paid' => 500000, // KURANG 300rb -> Status Merah
        ]);

        // KASUS 3: JATUH TEMPO (Ruko - Joko)
        // Harusnya bayar tahunan kemarin, tapi belum bayar lagi
        Lease::create([
            'property_id' => 4,
            'tenant_id' => 3,
            'start_date' => Carbon::now()->subYears(1)->subDays(5),
            'payment_frequency' => 'tahunan',
            'price' => 25000000,
            'last_payment_date' => Carbon::now()->subYears(1)->subDays(5), // Bayar tahun lalu
            'amount_paid' => 25000000,
            // Karena payment frequency tahunan, next_due_date = last_payment + 1 tahun
            // Berarti next_due_date = 5 hari yang lalu -> STATUS JATUH TEMPO (Merah/Oranye)
        ]);

        // KASUS 4: PENYEWA BARU (Kamar VIP - Rina)
        // Belum pernah bayar sama sekali
        Lease::create([
            'property_id' => 3,
            'tenant_id' => 4,
            'start_date' => Carbon::now(),
            'payment_frequency' => 'bulanan',
            'price' => 2000000,
            'last_payment_date' => null, // Belum bayar
            'amount_paid' => 0,
        ]);
    }
}