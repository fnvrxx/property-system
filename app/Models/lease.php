<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lease extends Model // Pastikan huruf L besar
{
    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'last_payment_date' => 'date',
        'next_due_date' => 'date',
    ];

    protected static function booted()
    {
        static::saving(function ($lease) {
            if ($lease->last_payment_date && $lease->payment_frequency) {
                $date = Carbon::parse($lease->last_payment_date);
                $lease->next_due_date = match ($lease->payment_frequency) {
                    'harian' => $date->copy()->addDay(),
                    'mingguan' => $date->copy()->addWeek(),
                    'bulanan' => $date->copy()->addMonth(),
                    'tahunan' => $date->copy()->addYear(),
                    default => $date,
                };
            } else {
                $lease->next_due_date = $lease->start_date;
            }
        });
    }

    // --- Atribut Virtual ---
    public function getShortageAttribute()
    {
        return max(0, $this->price - $this->amount_paid);
    }

    public function getStatusAttribute()
    {
        if ($this->shortage > 0)
            return 'kurang_bayar';
        if ($this->next_due_date && now()->gt($this->next_due_date))
            return 'jatuh_tempo';
        return 'lunas';
    }

    // --- RELASI (WAJIB ADA RETURN) ---

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class); // 👈 PASTIKAN INI ADA 'return'
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}