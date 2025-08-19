<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use App\Models\{Customer, Wallet, WalletTransaction};

class CustomerWallet extends Component
{
    use WithPagination;

    public int $customerId;
    public ?Customer $customer = null;
    public ?Wallet $wallet = null;

    // نموذج العملية
    public $type = 'deposit'; // deposit|withdraw|adjust
    public $amount;
    public $reference;
    public $note;

    // فلاتر الكشف
    public $from;
    public $to;
    public $q = '';

    public function mount(int $customerId)
    {
        $this->customerId = $customerId;
        $this->customer = Customer::with('agency')->findOrFail($customerId);
        $this->wallet = Wallet::firstOrCreate(['customer_id' => $customerId], [
            'balance' => 0, 'status' => 'active'
        ]);
    }

public function submit()
{
    $this->validate([
        'type' => 'required|in:deposit,withdraw,adjust',
        'amount' => 'required|numeric|min:0.01',
        'reference' => 'nullable|string|max:255',
        'note' => 'nullable|string|max:2000',
    ]);

    // 1) احفظ نوع العملية قبل أي تعديل
    $typeAtSubmit = $this->type;

    // 2) منطق الرصيد فقط داخل الترانزاكشن
    DB::transaction(function () {
        $wallet = Wallet::where('id', $this->wallet->id)->lockForUpdate()->first();

        $delta = (float) $this->amount;
        $newBalance = match ($this->type) {
            'deposit'  => $wallet->balance + $delta,
            'withdraw' => function () use ($wallet, $delta) {
                if ($wallet->balance < $delta) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'amount' => 'الرصيد غير كافٍ',
                    ]);
                }
                return $wallet->balance - $delta;
            },
            'adjust'   => $delta, // رصيد نهائي
        };
        if (is_callable($newBalance)) $newBalance = $newBalance();

        $txType = $this->type === 'adjust' ? 'adjust' : $this->type;

        WalletTransaction::create([
            'wallet_id'        => $wallet->id,
            'type'             => $txType,
            'amount'           => $this->type === 'adjust'
                                    ? abs($newBalance - $wallet->balance)
                                    : $delta,
            'running_balance'  => $newBalance,
            'reference'        => $this->reference ?: null,
            'note'             => $this->note ?: null,
        ]);

        $wallet->update(['balance' => $newBalance]);
    });

    // 3) تشغيل التصفية التلقائية بعد النجاح إن كانت العملية إيداع
    $autoApplied = 0.0;
    if ($typeAtSubmit === 'deposit') {
        $autoApplied = app(\App\Services\AutoSettlementService::class)
            ->autoSettle($this->customer, auth()->user()->name ?? 'Auto-Settle');
    }

    // 4) تحديث الواجهة
    $this->wallet->refresh();
    $this->reset(['type','amount','reference','note']);
    $this->type = 'deposit';

    session()->flash('message',
        $autoApplied > 0
        ? 'تم الإيداع وتصفية بقيمة ' . number_format($autoApplied, 2)
        : 'تم تنفيذ العملية'
    );
}


    public function getTransactionsProperty()
    {
        return WalletTransaction::where('wallet_id', $this->wallet->id)
            ->when($this->from, fn($q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->to, fn($q)   => $q->whereDate('created_at', '<=', $this->to))
            ->when($this->q, function ($q) {
                $q->where(function ($qq) {
                    $qq->where('reference', 'like', "%{$this->q}%")
                       ->orWhere('note', 'like', "%{$this->q}%")
                       ->orWhere('performed_by_name', 'like', "%{$this->q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(10);
    }

    public function render()
    {
        return view('livewire.agency.customer-wallet');
    }

    // CustomerWallet.php
public function updatingFrom() { $this->resetPage(); }
public function updatingTo()   { $this->resetPage(); }
public function updatingQ()    { $this->resetPage(); }


public function runAutoSettle()
{
    $applied = app(\App\Services\AutoSettlementService::class)
        ->autoSettle($this->customer, auth()->user()->name ?? 'Auto-Settle');

    $this->wallet->refresh();
    session()->flash('message', $applied > 0 ? 'تمت التصفية: '.number_format($applied,2) : 'لا يوجد ما يُصفّى');
}



}
