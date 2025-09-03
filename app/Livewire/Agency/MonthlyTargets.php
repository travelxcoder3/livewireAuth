<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\EmployeeMonthlyTarget;
use Illuminate\Support\Facades\Schema;

class MonthlyTargets extends Component
{
    public int $empYear, $empMonth;   // لتبويب الموظفين
    public int $colYear, $colMonth;   // لتبويب التحصيل
    public ?string $successMessage = null;
    public ?string $toastType = null; // success | error | warning | info

    public $sim = [
        'employee_id' => null,
        'sale' => 0.0,
        'cost' => 0.0,
        'method' => null,
        'collected' => 0.0,
        'result' => null,
    ];

    public $daysToDebt = 30;
    public $debtBehavior = 'deduct_commission_until_paid';
public function updatedSim()
{
    $this->clearToast();
}


public function simulate()
{
    $this->clearToast();
    $netMargin = max((float)$this->sim['sale'] - (float)$this->sim['cost'], 0);

    // حساب عمولة الموظف
    $employeeCommission = $netMargin * ($this->employeeRateFixed / 100);
    
    // حساب عمولة المحصل (مثال مبسط)
    $collectorCommission = 0;
    if ($this->sim['method'] && isset($this->collectorBaselines[$this->sim['method']])) {
        $rule = $this->collectorBaselines[$this->sim['method']];
        if ($rule['type'] === 'percent') {
            $collectorCommission = $employeeCommission * ($rule['value'] / 100);
        } else {
            $collectorCommission = $rule['value'];
        }
    }
    
    $employeeCommissionNet = max($employeeCommission - $collectorCommission, 0);
    $companyShare = max($netMargin - $employeeCommission, 0);

    $this->sim['result'] = [
        'net_margin'            => number_format($netMargin, 2),
        'employee_commission'   => number_format($employeeCommissionNet, 2),
        'collector_commission'  => number_format($collectorCommission, 2),
        'company_share'         => number_format($companyShare, 2),
    ];
}

    public string $tab = 'emp'; // emp | collector

    /** جدول الأهداف */
    public array $rows = [];

    /** رأس الصفحة: تثبيت % عمولة الموظف مرة واحدة من هنا */
    public ?float $employeeRateFixed = null;      // يعرض قيمة profile->employee_rate
    public bool  $employeeRateLocked = false;     // من commission_profiles.employee_rate_locked

    /** تبويب قواعد التحصيل الشهرية */
    public array $collectorBaselines = [];        // من CommissionCollectorRule (الـ8)
    public bool  $collectorBaselinesLocked = false;

    public array $collectorMonthly = [];          // للعرض فقط لكل طريقة في هذا الشهر
    public $employeeOptions = [];
    public $methodOptions = [
        'percent' => 'نسبة %',
        'fixed' => 'مبلغ ثابت'
    ];
    public function mount(): void
    {
            // استرجاع قائمة الموظفين من قاعدة البيانات
    $this->employeeOptions = User::where('agency_id', auth()->user()->agency_id)
                                  ->pluck('name', 'id')
                                  ->toArray();

    // المتغيرات الأخرى
    $this->empYear = $this->colYear  = (int)now()->year;
    $this->empMonth = $this->colMonth = (int)now()->month;
        $now = now();
        $this->empYear = $this->colYear  = (int)$now->year;
        $this->empMonth= $this->colMonth = (int)$now->month;

        $profile = \App\Models\CommissionProfile::where('agency_id', auth()->user()->agency_id)
            ->where('is_active', true)->with('collectorRules')->first();

        $this->employeeRateFixed        = $profile?->employee_rate ?? 0;
        $this->employeeRateLocked       = (bool)($profile?->employee_rate_locked ?? false);
        $this->collectorBaselinesLocked = (bool)($profile?->collector_baselines_locked ?? false);
    // تحميل سياسة الدين
    $this->daysToDebt = $profile?->days_to_debt ?? 30;
    $this->debtBehavior = $profile?->debt_behavior ?? 'deduct_commission_until_paid';
        $this->collectorBaselines = [];
        foreach ([1,2,3,4,5,6,7,8] as $m) {
            $r = optional($profile?->collectorRules->firstWhere('method',$m));
            $this->collectorBaselines[$m] = [
                'type'=>$r?->type ?? 'percent',
                'value'=>$this->fmtDec($r?->value),
                'basis'=>$r?->basis ?? 'collected_amount',
            ];
        }

        $this->loadRows();              // يعتمد الآن على empYear/empMonth
        $this->loadCollectorMonthly();  // يعتمد الآن على colYear/colMonth
    }
  
    public function loadMonth(): void
    {
        $this->clearToast();
        $this->loadRows();
        $this->loadCollectorMonthly();
    }

    private function monthHasSales(): bool
    {
        // تبقى كما هي لاستخدامها في تثبيت النسبة العامة (ملف التعريف)
        $agencyId = auth()->user()->agency_id;
        return \App\Models\Sale::where('agency_id',$agencyId)
            ->whereYear('sale_date', $this->empYear)
            ->whereMonth('sale_date',$this->empMonth)
            ->exists();
    }

    private function monthHasCollections(): bool
    {
        $agencyId = auth()->user()->agency_id;
        return \DB::table('collections')
            ->where('agency_id', $agencyId)
            ->whereYear('payment_date', $this->colYear)
            ->whereMonth('payment_date',$this->colMonth)
            ->exists();
    }

    /** إرجاع IDs الموظفين الذين لديهم مبيعات في شهر empYear/empMonth */
    private function saleUserIdsForMonth(): array
    {
        $agency = Auth::user()->agency;
        $agencyId = $agency->id;

        if ($agency->parent_id === null) {
            $branchIds = $agency->branches()->pluck('id')->toArray();
            $allAgencyIds = array_merge([$agencyId], $branchIds);
        } else {
            $allAgencyIds = [$agencyId];
        }

        // نقيّد بالمستخدمين الفعّالين ضمن نطاق الوكالة/الفروع
        $userIds = User::whereIn('agency_id', $allAgencyIds)
            ->where('is_active', true)
            ->pluck('id');

        return \App\Models\Sale::whereIn('agency_id', $allAgencyIds)
            ->whereIn('user_id', $userIds)
            ->whereYear('sale_date', $this->empYear)
            ->whereMonth('sale_date', $this->empMonth)
            ->pluck('user_id')->unique()->values()->all();
    }

    /** فحص سريع لموظف واحد */
    private function employeeMonthHasSales(int $userId): bool
    {
        return in_array($userId, $this->saleUserIdsForMonth(), true);
    }


    public function fixEmployeeRate(): void
    {
        $this->clearToast();
        if ($this->employeeRateLocked) { return; }

        // ممنوع التعديل إن وُجدت مبيعات في الشهر الجاري المختار
        if ($this->monthHasSales()) {
            $this->toastType = 'error';
            $this->successMessage = 'مرفوض: يوجد عمليات بيع في هذا الشهر، لا يمكن تثبيت/تعديل النسبة.';            return;
        }

        \DB::transaction(function () {
            $profile = \App\Models\CommissionProfile::firstOrCreate(
                ['agency_id'=>auth()->user()->agency_id, 'is_active'=>true],
                ['name'=>'الافتراضي','employee_rate'=>0]
            );

            // ثبّت القيمة لأول مرة ثم اقفلها
            $profile->employee_rate = (float)$this->employeeRateFixed;
            $profile->employee_rate_locked = true;
            $profile->save();
        });

        $this->employeeRateLocked = true;
        $this->toastType = 'success';   
        $this->successMessage = 'تم تثبيت نسبة عمولة الموظف نهائياً.';
    }


    public function updatedEmpYear()  { $this->clearToast(); $this->loadRows(); }
    public function updatedEmpMonth() { $this->clearToast(); $this->loadRows(); }

    public function updatedColYear()  { $this->clearToast(); $this->loadCollectorMonthly(); }
    public function updatedColMonth() { $this->clearToast(); $this->loadCollectorMonthly(); }

    
    public function copyCollectorFromPrev(): void
    {
        $this->clearToast();
        // ممنوع إذا كان هذا الشهر مُنشأ ومقفول أو فيه تحصيلات
        if ($this->monthHasCollections()) {
            $this->toastType = 'error';
$this->successMessage = 'مرفوض: توجد تحصيلات في هذا الشهر.';            return;
        }
        $agencyId = auth()->user()->agency_id;

        $exists = \App\Models\CommissionCollectorMonthly::where([
            'agency_id'=>$agencyId,
            'year' =>$this->colYear,
            'month'=>$this->colMonth,
        ])->exists();

        if ($exists) {
            $this->toastType = 'error';
$this->successMessage = 'هذا الشهر مُهيّأ ومقفول مسبقاً.';
            return;
        }


        $prev = now()->setDate($this->colYear, $this->colMonth, 1)->subMonth();


        $prevRows = \App\Models\CommissionCollectorMonthly::where([
            'agency_id'=>$agencyId,'year'=>$prev->year,'month'=>$prev->month,
        ])->get()->keyBy('method');

        foreach ([1,2,3,4,5,6,7,8] as $m) {
            if ($r = $prevRows->get($m)) {
                $this->collectorMonthly[$m]['type']  = $r->type;
                $this->collectorMonthly[$m]['value'] = $this->fmtDec($r->value);
                $this->collectorMonthly[$m]['basis'] = $r->basis;
            }
            // إن لم يوجد في السابق نبقى على القاعدة الأساسية المحمّلة مسبقًا
        }
        $this->toastType = 'success';  
        $this->successMessage = 'تم نسخ قواعد التحصيل من الشهر السابق (غير محفوظة بعد).';
    }


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
            ->where('year',  $this->empYear)
            ->where('month', $this->empMonth)
            ->get()
            ->keyBy('user_id');


    // IDs الموظفين الذين لديهم مبيعات في شهر الموظفين المحدد
    $saleUserIds = \App\Models\Sale::whereIn('user_id', $users->pluck('id'))
        ->whereYear('sale_date', $this->empYear)
        ->whereMonth('sale_date', $this->empMonth)
        ->pluck('user_id')->unique()->toArray();

    $this->rows = $users->map(function ($u) use ($targets, $saleUserIds) {
        $t = $targets->get($u->id); // قد يكون null
        $lockedBySales = in_array($u->id, $saleUserIds, true);

        return [
            'row_id'       => $t?->id,
            'user_id'      => $u->id,
            'name'         => $u->name,
            'main_target'  => (float)($t?->main_target ?? $u->main_target ?? 0),
            'sales_target' => (float)($t?->sales_target ?? $u->sales_target ?? 0),
            'override_rate'=> ($t?->override_rate !== null) ? (float)$t->override_rate : null,
            // القفل النهائي = إن كان السجل الشهري مقفول أو توجد مبيعات لهذا الموظف في هذا الشهر
            'locked'       => (bool)($t?->locked ?? false) || $lockedBySales,
        ];
    })->values()->all();

        $this->clearToast();
    }

    public function copyEmpFromPrev(): void
    {
        $this->clearToast();
        $prev = now()->setDate($this->empYear, $this->empMonth, 1)->subMonth();
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
        $this->toastType = 'success';
        $this->successMessage = 'تم نسخ أهداف الشهر السابق';
    }

    public function saveAll(): void
    {
        $this->clearToast();
        $agencyId = Auth::user()->agency_id;

        $saleUserIds = $this->saleUserIdsForMonth();
        $skippedBySales = [];
        $skippedExisting = [];
        $createdCount = 0;

        DB::transaction(function () use ($agencyId, $saleUserIds, &$skippedBySales, &$skippedExisting, &$createdCount) {
            foreach ($this->rows as &$r) {
                // ممنوع إن لدى هذا الموظف مبيعات في الشهر
                if (in_array($r['user_id'], $saleUserIds, true)) {
                    $skippedBySales[] = $r['name'];
                    continue;
                }

                // إن كان الصف مقفولاً مسبقاً لا نعدّل
                if ($r['locked'] ?? false) {
                    $skippedExisting[] = $r['name'];
                    continue;
                }

                $rec = EmployeeMonthlyTarget::firstOrNew([
                    'agency_id' => $agencyId,
                    'user_id'   => $r['user_id'],
                    'year'      => $this->empYear,
                    'month'     => $this->empMonth,
                ]);

                // إن كان موجوداً مسبقاً نعتبره متخطّى
                if ($rec->exists) {
                    $skippedExisting[] = $r['name'];
                    continue;
                }

                $rec->main_target   = (float)($r['main_target'] ?? 0);
                $rec->sales_target  = (float)($r['sales_target'] ?? 0);
                $rec->override_rate = $r['override_rate'] !== null ? (float)$r['override_rate'] : null;
                $rec->locked        = true;
                $rec->updated_by    = Auth::id();
                $rec->created_by    = Auth::id();
                $rec->save();

                $r['row_id'] = $rec->id;
                $r['locked'] = true;
                $createdCount++;
            }
        });

        $this->loadRows();

        // رسالة دقيقة حسب ما حصل
        if ($createdCount > 0 && empty($skippedBySales) && empty($skippedExisting)) {
            $this->toastType = 'success';
            $this->successMessage = 'تم إنشاء الأهداف لهذا الشهر وقفلها.';
        } else {
            // أعطِها أحمر عند أي تخطّي حتى تكون واضحة
            $this->toastType = ($skippedBySales || $skippedExisting) ? 'error' : 'success';
            $parts = [];
            if ($createdCount > 0)      $parts[] = "تم إنشاء {$createdCount}";
            if ($skippedBySales)        $parts[] = 'تخطِّي بسبب مبيعات: ' . implode('، ', $skippedBySales);
            if ($skippedExisting)       $parts[] = 'تخطِّي سجلات موجودة/مقفولة: ' . implode('، ', $skippedExisting);
            $this->successMessage = implode(' | ', $parts) ?: 'لا تغييرات.';
        }

    }
    
    public function saveRow(int $i): void
    {
        $this->clearToast();
        $r = $this->rows[$i] ?? null;
        if (!$r) { return; }

        $agencyId = Auth::user()->agency_id;

        // منع الحفظ إن لدى الموظف مبيعات في هذا الشهر
        if ($this->employeeMonthHasSales((int)$r['user_id'])) {
            $this->toastType = 'error';
            $this->successMessage = 'مرفوض: لدى هذا الموظف مبيعات في هذا الشهر.';
            return;
        }

        // إن كان الصف مقفولاً لا نعدل
        if ($r['locked'] ?? false) {
            $this->toastType = 'error';
            $this->successMessage = 'هذا الصف مقفول.';
            return;
        }

        $rec = EmployeeMonthlyTarget::firstOrNew([
            'agency_id' => $agencyId,
            'user_id'   => (int)$r['user_id'],
            'year'      => $this->empYear,
            'month'     => $this->empMonth,
        ]);

        if ($rec->exists) {
            $this->toastType = 'error';
            $this->successMessage = 'تم إنشاء سجل لهذا الموظف مسبقاً لهذا الشهر.';
            return;
        }

        $rec->main_target   = (float)($r['main_target'] ?? 0);
        $rec->sales_target  = (float)($r['sales_target'] ?? 0);
        $rec->override_rate = $r['override_rate'] !== null ? (float)$r['override_rate'] : null;
        $rec->locked        = true;
        $rec->updated_by    = Auth::id();
        $rec->created_by    = Auth::id();
        $rec->save();

        // حدث الصف محلياً لتنعكس الحالة فوراً
        $this->rows[$i]['row_id'] = $rec->id;
        $this->rows[$i]['locked'] = true;

        $this->toastType = 'success';
        $this->successMessage = 'تم حفظ هذا الموظف وقفل صفه.';
    }

public function saveDebtPolicy()
{
    $this->clearToast();
    // التحقق من البيانات المدخلة قبل حفظها
    $this->validate([
        'daysToDebt'   => 'required|integer|min:0',
        'debtBehavior' => 'required|in:deduct_commission_until_paid,hold_commission',
    ]);

    // حفظ سياسة الدين في جدول commission_profiles
    DB::transaction(function () {
        $profile = \App\Models\CommissionProfile::firstOrCreate(
            ['agency_id' => Auth::user()->agency_id, 'is_active' => true],
            ['name' => 'الافتراضي']
        );
        
        $profile->days_to_debt = $this->daysToDebt;
        $profile->debt_behavior = $this->debtBehavior;
        $profile->save();
    });

    $this->toastType = 'success';
    $this->successMessage = 'تم حفظ سياسة الدين بنجاح.';
}


    public function loadCollectorMonthly(): void
    {
        $this->collectorMonthly = [];

        // لو الجدول غير موجود لا تستعلم عنه
        if (!Schema::hasTable('commission_collector_monthlies')) {
            foreach ([1,2,3,4,5,6,7,8] as $m) {
                $b = $this->collectorBaselines[$m];
                $this->collectorMonthly[$m] = [
                    'exists'=>false,'type'=>$b['type'],'value'=>$b['value'],'basis'=>$b['basis'],'locked'=>false,
                ];
            }
            return;
        }

        $agencyId = auth()->user()->agency_id;
            $rows = \App\Models\CommissionCollectorMonthly::where([
                'agency_id'=>auth()->user()->agency_id,
                'year' =>$this->colYear,
                'month'=>$this->colMonth,
            ])->get()->keyBy('method');


        foreach ([1,2,3,4,5,6,7,8] as $m) {
            $rec = $rows->get($m); $b = $this->collectorBaselines[$m];
            $this->collectorMonthly[$m] = [
                'exists'=>(bool)$rec,
                'type'  =>$rec->type  ?? $b['type'],
                'value' =>$this->fmtDec($rec->value ?? $b['value']),
                'basis' =>$rec->basis ?? $b['basis'],
                'locked'=>(bool)($rec->locked ?? false),
            ];
        }
        $this->clearToast();
    }


    /** إنشاء ضبط الشهر لأول مرة فقط ثم يُقفل نهائياً */
    public function createCollectorForMonth(): void
    {
        $this->clearToast();
        if ($this->monthHasCollections()) {
            $this->toastType = 'error';
            $this->successMessage = 'مرفوض: توجد تحصيلات في هذا الشهر؛ لا يمكن إنشاء أو تعديل قواعد التحصيل.';
            return;
        }

        $agencyId = auth()->user()->agency_id;

        // لا نسمح إن كانت موجودة
        $exists = \App\Models\CommissionCollectorMonthly::where([
            'agency_id'=>$agencyId,
            'year' =>$this->colYear,
            'month'=>$this->colMonth,
        ])->exists();

        if ($exists) {
            $this->toastType = 'error';
$this->successMessage = 'هذه القواعد موجودة ومقفولة سلفاً.';
            return;
        }

        \DB::transaction(function () use ($agencyId) {
            foreach ([1,2,3,4,5,6,7,8] as $m) {
                \App\Models\CommissionCollectorMonthly::create([
                    'agency_id'=>$agencyId,
                    'year' =>$this->colYear,
                    'month'=>$this->colMonth,
                    'method'=>$m,
                    'type'=>$this->collectorMonthly[$m]['type'],
                    'value'=>(float)$this->collectorMonthly[$m]['value'],
                    'basis'=>$this->collectorMonthly[$m]['basis'],
                    'locked'=>true,
                ]);

            }
        });

        $this->loadCollectorMonthly();
        $this->toastType = 'success'; 
        $this->successMessage = 'تم إنشاء قواعد التحصيل للشهر وقفلها.';
    }

    public function fixCollectorBaselines(): void
    {
        $this->clearToast();
        if ($this->collectorBaselinesLocked) { return; }

        \DB::transaction(function () {
            $profile = \App\Models\CommissionProfile::firstOrCreate(
                ['agency_id'=>auth()->user()->agency_id, 'is_active'=>true],
                ['name'=>'الافتراضي']
            );

            // اكتب القواعد الأساسية الثمانية
            \App\Models\CommissionCollectorRule::where('profile_id', $profile->id)->delete();

            $rows = [];
            foreach ([1,2,3,4,5,6,7,8] as $m) {
                $b = $this->collectorBaselines[$m] ?? ['type'=>null,'value'=>null,'basis'=>null];
                $rows[] = [
                    'profile_id' => $profile->id,
                    'method'     => $m,
                    'type'       => $b['type'] ?? null,
                    'value'      => $b['value'] ?? null,
                    'basis'      => $b['basis'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if ($rows) {
                \App\Models\CommissionCollectorRule::insert($rows);
            }

            // اقفلها نهائيًا
            $profile->collector_baselines_locked = true;
            $profile->save();
        });

        $this->collectorBaselinesLocked = true;
        $this->toastType = 'success';
        $this->successMessage = 'تم تثبيت قواعد التحصيل الأساسية نهائياً.';
    }

    public function toggleLock(int $userId): void
    {
        $this->clearToast();
        // إن لدى الموظف مبيعات في هذا الشهر نمنع فك القفل أو تغييره
        if ($this->employeeMonthHasSales($userId)) {
            $this->toastType = 'error';
            $this->successMessage = 'مرفوض: لدى هذا الموظف مبيعات في هذا الشهر.';
            return;
        }

        $rec = EmployeeMonthlyTarget::where([
            'user_id' => $userId,
            'year'    => $this->empYear,
            'month'   => $this->empMonth,
        ])->first();

        if ($rec) {
            $rec->locked = !$rec->locked;
            $rec->save();
            $this->loadRows();
        }
    }

    public function updatedTab()
    {
        $this->clearToast();
    }


    private function clearToast(): void
    {
        $this->successMessage = null;
        $this->toastType = null;
    }
    private function fmtDec(null|float|string $v): string {
        if ($v === null) return '0';
        $s = rtrim(rtrim((string)$v, '0'), '.');
        return $s === '' ? '0' : $s;
    }

    public function render()
    {
        return view('livewire.agency.monthly-targets')
            ->layout('layouts.agency')
            ->title('أهداف الموظفين الشهرية');
    }
}
