<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Services\AutoSettlementService;

class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id','type','amount','running_balance',
        'reference','note','performed_by_name'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'running_balance' => 'decimal:2',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function attachments()
    {
        return $this->hasMany(WalletAttachment::class, 'transaction_id');
    }

   // app/Models/WalletTransaction.php
protected static function booted()
{
    // تعبئة اسم المنفذ
    static::creating(function (self $model) {
        if (blank($model->performed_by_name) && auth()->check()) {
            $model->performed_by_name = auth()->user()->name;
        }
    });

    // تشغيل التصفية تلقائيًا بعد كل إيداع
    static::created(function (self $tx) {
        if ($tx->type !== 'deposit') return;

        DB::afterCommit(function () use ($tx) {
            $customer = optional($tx->wallet)->customer()->with('agency')->first();
            if (!$customer) return;

            app(\App\Services\AutoSettlementService::class)->autoSettle(
                $customer,
                $tx->performed_by_name ?: (auth()->user()->name ?? 'System')
            );
        });
    });
}

}
