<?php

namespace App\Filament\Widgets;

use App\Models\Lease;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestDueLeases extends BaseWidget
{
    // Judul Widget
    protected static ?string $heading = '⚠️ Tagihan Jatuh Tempo & Kurang Bayar';

    // Urutan widget (opsional, biar di bawah kartu statistik)
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Ambil data yang Jatuh Tempo ATAU Kurang Bayar
                Lease::query()
                    ->where('next_due_date', '<=', now()->addDays(3)) // H-3 sampai Lewat Tanggal
                    ->orWhereRaw('price > amount_paid')
            )
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Penyewa')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('property.name')
                    ->label('Properti'),

                Tables\Columns\TextColumn::make('next_due_date')
                    ->date()
                    ->label('Tgl Jatuh Tempo')
                    ->color(fn($state) => $state < now() ? 'danger' : 'warning'),

                Tables\Columns\TextColumn::make('shortage')
                    ->money('IDR')
                    ->label('Kekurangan')
                    ->color('danger'),
            ])
            ->actions([
                // Kita pasang tombol WA di sini juga biar cepat!
                Tables\Actions\Action::make('tagih_cepat')
                    ->label('WA')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(fn(Lease $record) => \App\Filament\Resources\Leases\LeaseResource::getUrl('index')), // Arahkan ke menu utama untuk kirim
            ])
            ->paginated(false); // Tampilkan 5-10 data saja tanpa pagination
    }
}