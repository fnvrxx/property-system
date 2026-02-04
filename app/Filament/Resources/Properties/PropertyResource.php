<?php

namespace App\Filament\Resources\Properties;

use App\Filament\Resources\Properties\Pages;
use App\Models\Property;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $recordTitleAttribute = 'property';

    protected static ?string $navigationLabel = 'Data Properti';
    protected static ?string $slug = 'properties';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Aset')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->label('Nama Properti')
                            ->placeholder('Contoh: Kamar A1, Ruko Blok B')
                            ->maxLength(255),

                        Select::make('type')
                            ->options([
                                'kos' => 'Kamar Kos',
                                'tanah' => 'Tanah Kavling',
                                'bangunan' => 'Ruko / Bangunan',
                                'apartemen' => 'Apartemen',
                            ])
                            ->required()
                            ->default('kos')
                            ->label('Jenis Aset'),

                        TextInput::make('base_price')
                            ->numeric()
                            ->prefix('Rp')
                            ->label('Harga Dasar')
                            ->helperText('Harga standar sewa (bisa diubah saat transaksi)'),

                        Toggle::make('is_available')
                            ->label('Tersedia?')
                            ->default(true)
                            ->onIcon('heroicon-m-check')
                            ->offIcon('heroicon-m-x-mark')
                            ->onColor('success')
                            ->offColor('danger'),

                        Textarea::make('address')
                            ->label('Alamat / Lokasi')
                            ->rows(3)
                            ->columnSpanFull(),
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
                    ->label('Nama Aset')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->label('Tipe')
                    ->colors([
                        'primary' => 'kos',
                        'warning' => 'tanah',
                        'success' => 'bangunan',
                    ])
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('base_price')
                    ->money('IDR')
                    ->label('Harga Dasar'),

                Tables\Columns\IconColumn::make('is_available')
                    ->boolean()
                    ->label('Status')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'kos' => 'Kamar Kos',
                        'tanah' => 'Tanah',
                        'bangunan' => 'Bangunan',
                    ]),
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
            'index' => Pages\ListProperties::route('/'),
            'create' => Pages\CreateProperty::route('/create'),
            'edit' => Pages\EditProperty::route('/{record}/edit'),
        ];
    }
}
