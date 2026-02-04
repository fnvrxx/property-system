<?php

namespace App\Filament\Resources\Leases;

// use App\Filament\Resources\Leases\LeaseResource\Pages; 
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use App\Models\Lease;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Leases\Pages;

class LeaseResource extends Resource
{
    protected static ?string $model = Lease::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';
    protected static ?string $navigationLabel = 'Transaksi Sewa';
    protected static ?string $slug = 'leases'; // Custom URL slug agar rapi

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Kontrak')
                    ->schema([
                        // Pilih Penghuni
                        Select::make('tenant_id')
                            ->relationship('tenant', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Penyewa'),

                        // Pilih Properti
                        Select::make('property_id')
                            ->relationship('property', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Properti'),

                        // Tipe Bayar
                        Select::make('payment_frequency')
                            ->options([
                                'harian' => 'Harian',
                                'mingguan' => 'Mingguan',
                                'bulanan' => 'Bulanan',
                                'tahunan' => 'Tahunan',
                            ])
                            ->required()
                            ->label('Tipe Bayar'),

                        DatePicker::make('start_date')
                            ->required()
                            ->label('Tanggal Masuk'),
                    ])->columns(2),

                Forms\Components\Section::make('Pembayaran Terkini')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->label('Tarif Sewa'),

                        Forms\Components\DatePicker::make('last_payment_date')
                            ->label('Tanggal Bayar Terakhir')
                            ->helperText('Jatuh tempo dihitung dari tanggal ini.'),

                        Forms\Components\TextInput::make('amount_paid')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->label('Jumlah Dibayar'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Penyewa')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('property.name')
                    ->label('Properti')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Status')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'kurang_bayar' => 'KURANG BAYAR',
                        'jatuh_tempo' => 'JATUH TEMPO',
                        'lunas' => 'LUNAS',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'kurang_bayar' => 'danger',
                        'jatuh_tempo' => 'warning',
                        'lunas' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('next_due_date')
                    ->date()
                    ->sortable()
                    ->label('Jatuh Tempo'),

                Tables\Columns\TextColumn::make('shortage')
                    ->money('IDR')
                    ->label('Kurang Bayar')
                    ->color(fn($state) => $state > 0 ? 'danger' : 'success'),
            ])
            ->defaultSort('next_due_date', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status_filter')
                    ->label('Filter Status')
                    ->options([
                        'kurang_bayar' => 'Kurang Bayar',
                        'jatuh_tempo' => 'Jatuh Tempo',
                        'lunas' => 'Lunas',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'kurang_bayar') {
                            return $query->whereRaw('price > amount_paid');
                        }
                        if ($data['value'] === 'jatuh_tempo') {
                            return $query->where('next_due_date', '<', now());
                        }
                        return $query;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // 👇 TOMBOL KIRIM WA BARU
                Tables\Actions\Action::make('kirim_tagihan')
                    ->label('Tagih WA')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success') // Warna Hijau
                    ->requiresConfirmation() // Minta konfirmasi dulu biar gak kepencet
                    ->modalHeading('Kirim Tagihan WhatsApp')
                    ->modalDescription('Apakah Anda yakin ingin mengirim rincian tagihan ke penyewa ini?')
                    ->modalSubmitActionLabel('Ya, Kirim Sekarang')
                    ->action(function (Lease $record) {
                        // 1. Siapkan Pesan
                        $pesan = "Halo *{$record->tenant->name}*,\n\n" .
                            "Kami mengingatkan rincian pembayaran sewa properti Anda:\n" .
                            "🏠 Properti: {$record->property->name}\n" .
                            "📅 Jatuh Tempo: " . ($record->next_due_date ? $record->next_due_date->format('d M Y') : '-') . "\n" .
                            "💰 Total Tagihan: Rp " . number_format($record->price, 0, ',', '.') . "\n" .
                            "💵 Sudah Dibayar: Rp " . number_format($record->amount_paid, 0, ',', '.') . "\n\n" .
                            "Mohon segera melakukan pembayaran. Terima kasih!";

                        // 2. Kirim via Service Fonnte
                        // Pastikan nomor HP ada
                        if (!$record->tenant->phone_number) {
                            \Filament\Notifications\Notification::make()
                                ->title('Gagal')
                                ->body('Nomor HP penyewa tidak ditemukan!')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Panggil Service yang tadi kita buat
                        $response = \App\Services\FonnteService::send(
                            $record->tenant->phone_number,
                            $pesan
                        );

                        // 3. Notifikasi Sukses/Gagal di Layar Admin
                        if (isset($response['status']) && $response['status'] == true) {
                            \Filament\Notifications\Notification::make()
                                ->title('Terkirim')
                                ->body('Pesan WhatsApp berhasil dikirim ke antrean Fonnte.')
                                ->success()
                                ->send();
                        } else {
                            // \Filament\Notifications\Notification::make()
                            //     ->title('Gagal Kirim')
                            //     ->body('Cek Token Fonnte Anda atau koneksi internet.')
                            //     ->danger()
                            //     ->send();
                            // 👇 MODIFIKASI BAGIAN INI UNTUK DEBUGGING
                            // Kita ambil pesan error asli dari Fonnte/Server
                            $errorDetail = $response['reason'] ?? $response['detail'] ?? 'Tidak ada respon dari server';

                            \Filament\Notifications\Notification::make()
                                ->title('Gagal Kirim')
                                ->body('Info Error: ' . $errorDetail) // Tampilkan error aslinya disini
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeases::route('/'),
            'create' => Pages\CreateLease::route('/create'),
            'edit' => Pages\EditLease::route('/{record}/edit'),
        ];
    }
}