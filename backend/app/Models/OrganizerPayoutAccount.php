<?php

declare(strict_types=1);

namespace HiEvents\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizerPayoutAccount extends Model
{
    protected $table = 'organizer_payout_accounts';

    protected $fillable = [
        'organizer_id',
        'account_id',
        'bank_name',
        'account_number',
        'account_name',
        'currency',
    ];
}
