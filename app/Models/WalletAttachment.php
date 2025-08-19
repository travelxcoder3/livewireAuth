<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletAttachment extends Model
{
    protected $fillable = ['transaction_id','path','mime','size'];

    public function transaction()
    {
        return $this->belongsTo(WalletTransaction::class, 'transaction_id');
    }
}
