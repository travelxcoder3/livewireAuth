<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SweepAutoSettlements implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
   public function handle(AutoSettlementService $svc)
{
    \App\Models\Customer::whereHas('wallet', fn($q)=>$q->where('balance','>',0))
      ->whereHas('sales', fn($q)=>$q->whereRaw('(usd_sell - amount_paid) > 0'))
      ->chunkById(100, function($customers) use ($svc){
          foreach ($customers as $c) $svc->autoSettle($c, 'Scheduler');
      });
}

}
