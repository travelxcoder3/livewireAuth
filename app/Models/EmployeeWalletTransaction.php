<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeWalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id','type','amount','running_balance',
        'reference','note','performed_by_name'
    ];

    public function wallet() {
        return $this->belongsTo(EmployeeWallet::class, 'wallet_id');
    }
}
