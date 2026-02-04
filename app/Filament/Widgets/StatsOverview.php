<?php

namespace App\Filament\Widgets;

use App\Models\Lease;
use App\Models\Property;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    // Opsional: Atur agar widget ini refresh otomatis setiap 15 detik
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        // 1. Hitung Total Pemasukan (Semua waktu)
        $totalPemasukan = Lease::sum('amount_paid');

        // 2. Hitung Properti yang Terisi (Dari total properti)
        $totalProperti = Property::count();
        $terisi = Lease::where('is_active', true)->count(); // Asumsi sewa aktif

        // 3. Hitung Tagihan 'Jatuh Tempo' & 'Kurang Bayar'
        // Logic: Lewat tanggal jatuh tempo ATAU belum lunas (price > paid)
        $perluDitagih = Lease::where('next_due_date', '<', now())
            ->orWhereRaw('price > amount_paid')
            ->count();

        return [
            Stat::make('Total Pemasukan', 'Rp ' . number_format($totalPemasukan, 0, ',', '.'))
                ->description('Akumulasi pembayaran diterima')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17]), // Grafik mini hiasan

            Stat::make('Okupansi Properti', $terisi . ' / ' . $totalProperti . ' Unit')
                ->description('Jumlah unit yang sedang disewa')
                ->descriptionIcon('heroicon-m-home-modern')
                ->color('primary'),

            Stat::make('Perlu Ditagih', $perluDitagih . ' Penyewa')
                ->description('Jatuh Tempo / Kurang Bayar')
                ->descriptionIcon('heroicon-m-bell-alert')
                ->color('danger') // Merah biar eye-catching
                ->chart([10, 5, 2, 10]),
        ];
    }
}