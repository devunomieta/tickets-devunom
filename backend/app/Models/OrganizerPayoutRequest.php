<?php

declare(strict_types=1);

namespace HiEvents\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizerPayoutRequest extends Model
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_PAID = 'PAID';
    public const STATUS_REJECTED = 'REJECTED';

    protected $table = 'organizer_payout_requests';

    protected $fillable = [
        'organizer_id',
        'account_id',
        'amount',
        'currency',
        'status',
        'bank_name',
        'account_number',
        'account_name',
        'transfer_reference',
        'admin_note',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'paid_at' => 'datetime',
    ];
}
