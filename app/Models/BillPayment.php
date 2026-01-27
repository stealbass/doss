<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillPayment extends Model
{
    protected $fillable = [
        'bill_id',
        'date',
        'amount',
        'method',
        'note',
        'status',
        'order_id',
        'currency',
        'txn_id',
        'reciept',
    ];
}
