<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\EmployeeMonthlyTarget;

class MonthlyTargets extends Component
{
    public int $year;
    public int $month;
    public ?string $successMessage = null;

    /** @var array<int,array{row_id:?int,user_id:int,name:string,main_target:float,sales_target:float,override_rate:?float,locked:bool}> */
    public array $rows = [];

    public function mount(): void
    {
        $now = now();
        $this->year  = (int) $now->year;
        $this->month = (int) $now->month;
        $this->loadRows();
    }

    public function updatedYear()  { $this->loadRows(); }
    public function updatedMonth() { $this->loadRows(); }

    public function loadRows(): void
    {
        $agency = Auth::user()->agency;
        $agencyId = $agency->id;

        // مستخدمو الوكالة: إن كانت رئيسية فمع الفروع، وإلا فقط الفرع
        if ($agency->parent_id === null) {
            $branchIds = $agency->branches()->pluck('id')->toArray();
            $allAgencyIds = array_merge([$agencyId], $branchIds);
            $users = User::whereIn('agency_id', $allAgencyIds)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id','name','agency_id']);
        } else {
            $users = $agency->users()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id','name','agency_id']);
        }

        $targets = EmployeeMonthlyTarget::whereIn('user_id', $users->pluck('id'))
            ->where('year',  $this->year)
            ->where('month', $this->month)
            ->get()
            ->keyBy('user_id');

     $this->rows = $users->map(function ($u) use ($targets) {
    $t = $targets->get($u->id); // قد يكون null

    return [
        'row_id'       => $t?->id,
        'user_id'      => $u->id,
        'name'         => $u->name,
        // إن لم يوجد سجل شهري خذ القيمة من user كافتراضي
        'main_target'  => (float)($t?->main_target ?? $u->main_target ?? 0),
        'sales_target' => (float)($t?->sales_target ?? $u->sales_target ?? 0),
        'override_rate'=> ($t?->override_rate !== null) ? (float)$t->override_rate : null,
        'locked'       => (bool)($t?->locked ?? false),
    ];
})->values()->all();

    }

    public function copyFromPrev(): void
    {
        $prev = now()->setDate($this->year, $this->month, 1)->subMonth();

        $prevTargets = EmployeeMonthlyTarget::whereIn('user_id', collect($this->rows)->pluck('user_id'))
            ->where('year',  $prev->year)
            ->where('month', $prev->month)
            ->get()
            ->keyBy('user_id');

        foreach ($this->rows as &$r) {
            if ($r['locked'] ?? false) continue;
            if ($p = $prevTargets->get($r['user_id'])) {
                $r['main_target']   = (float)$p->main_target;
                $r['sales_target']  = (float)$p->sales_target;
                $r['override_rate'] = $p->override_rate !== null ? (float)$p->override_rate : null;
            }
        }
        $this->successMessage = 'تم نسخ أهداف الشهر السابق';
    }

    public function saveAll(): void
    {
        $agencyId = Auth::user()->agency_id;

        DB::transaction(function () use ($agencyId) {
            foreach ($this->rows as &$r) {
                if ($r['locked'] ?? false) continue;

                $rec = EmployeeMonthlyTarget::firstOrNew([
                    'agency_id' => $agencyId,
                    'user_id'   => $r['user_id'],
                    'year'      => $this->year,
                    'month'     => $this->month,
                ]);

                $rec->main_target   = (float)($r['main_target'] ?? 0);
                $rec->sales_target  = (float)($r['sales_target'] ?? 0);
                $rec->override_rate = $r['override_rate'] !== null ? (float)$r['override_rate'] : null;
                $rec->updated_by    = Auth::id();
                if (!$rec->exists) $rec->created_by = Auth::id();
                $rec->save();

                $r['row_id'] = $rec->id;
            }
        });

        $this->loadRows();
        $this->successMessage = 'تم حفظ الأهداف بنجاح';
    }

    public function toggleLock(int $userId): void
    {
        $rec = EmployeeMonthlyTarget::where([
            'user_id' => $userId,
            'year'    => $this->year,
            'month'   => $this->month,
        ])->first();

        if ($rec) {
            $rec->locked = !$rec->locked;
            $rec->save();
            $this->loadRows();
        }
    }

    public function render()
    {
        return view('livewire.agency.monthly-targets')
            ->layout('layouts.agency')
            ->title('أهداف الموظفين الشهرية');
    }
}
