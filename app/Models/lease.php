<?php

namespace App\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;


class lease extends Model
{
    // Izinkan semua kolom diisi
    protected $guarded = [];

    // Pastikan kolom tanggal dibaca sebagai objek Date
    protected $casts = [
        'start_date' => 'date',
        'last_payment_date' => 'date',
        'next_due_date' => 'date',
    ];

    /**
     * LOGIKA OTOMATIS (Mirip Rumus Excel)
     * Menghitung Next Due Date setiap kali data disimpan.
     */
    protected static function booted()
    {
        static::saving(function ($lease) {
            // Jika ada Tanggal Bayar Terakhir, hitung Jatuh Tempo berikutnya
            if ($lease->last_payment_date && $lease->payment_frequency) {
                $date = Carbon::parse($lease->last_payment_date);

                // Rumus: IF(Harian, +1, IF(Mingguan, +7, ...)) [cite: 27-30]
                $lease->next_due_date = match ($lease->payment_frequency) {
                    'harian' => $date->copy()->addDay(),
                    'mingguan' => $date->copy()->addWeek(),
                    'bulanan' => $date->copy()->addMonth(),
                    'tahunan' => $date->copy()->addYear(),
                    default => $date,
                };
            } else {
                // Jika belum pernah bayar, jatuh tempo = tanggal mulai
                $lease->next_due_date = $lease->start_date;
            }
        });
    }

    /**
     * Hitung Kurang Bayar (Virtual Column)
     * Rumus: Tarif - Jumlah Dibayar [cite: 33]
     */
    public function getShortageAttribute()
    {
        return max(0, $this->price - $this->amount_paid);
    }

    /**
     * Tentukan Status (Virtual Column)
     * Rumus: IF(Kurang>0, "Kurang Bayar", IF(Now>JatuhTempo, "Jatuh Tempo", "Lunas")) [cite: 36-37]
     */
    public function getStatusAttribute()
    {
        if ($this->shortage > 0) {
            return 'kurang_bayar';
        }

        if ($this->next_due_date && now()->gt($this->next_due_date)) {
            return 'jatuh_tempo';
        }

        return 'lunas';
    }

    // --- RELASI TABLE ---
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
