<?php

namespace App\Filament\Resources\Tenants;

use App\Filament\Resources\Tenants\Pages;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Data Penyewa';
    protected static ?string $slug = 'penyewa';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Biodata Penyewa')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->label('Nama Lengkap')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone_number')
                            ->tel()
                            ->required()
                            ->label('Nomor WhatsApp')
                            ->placeholder('Contoh: 08123456789')
                            ->helperText('Gunakan nomor telepon yang aktif di WhatsApp'),

                        Forms\Components\TextInput::make('identity_number')
                            ->label('No. KTP / Identitas')
                            ->placeholder('NIK atau Nomor SIM (Opsional)')
                            ->maxLength(20),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Nama')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('phone_number')
                    ->searchable()
                    ->copyable()
                    ->label('WhatsApp')
                    ->icon('heroicon-m-phone'),

                Tables\Columns\TextColumn::make('identity_number')
                    ->label('No. Identitas')
                    ->toggleable(isToggledHiddenByDefault: true), // Sembunyikan default agar tabel rapi

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Terdaftar Pada'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
