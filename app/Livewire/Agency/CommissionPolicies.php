<?php
namespace App\Livewire\Agency;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\{CommissionProfile, CommissionCollectorRule, CommissionEmployeeRateOverride, CommissionCollectorOverride};
class CommissionPolicies extends Component
{
    public $agency;

    // افتراضي عام
    public $employeeRate = 0; // %

    // طرق التحصيل
    public $collectionMethods = [
      
        1 => 'مباشر عبر المحصّل',
        2 => 'غير مباشر متابعة الموظف',
        3 => 'عبر الموظف مباشرة',
        4 => 'متعثر مباشر',
        5 => 'متعثر غير مباشر',
        6 => 'شبه معدوم مباشر',
        7 => 'شبه معدوم غير مباشر',
        8 => 'معدوم مباشر',
    ];

    // قواعد المُحصّل العامة لكل طريقة
    public $collectorRules = []; // [method] => ['type','value','basis']

    // استثناءات: نسب موظف خاصة
    public $employeeRateOverridesRows = []; // [['employee_id'=>null,'rate'=>null],...]

    // استثناءات: قواعد مُحصّل خاصة بموظف+طريقة
    public $collectorOverrideRows = []; // [['employee_id'=>null,'method'=>1,'type'=>'percent','value'=>0,'basis'=>'collected_amount'],...]

    // سياسة الدين
    public $daysToDebt = 30;
    public $debtBehavior = 'deduct_commission_until_paid';


    private function normalizeMethodKey($key): ?int
{
    if ($key === '' || $key === 'null' || $key === null) {
        return null; // بدون تحصيل
    }
    return is_numeric($key) ? (int)$key : null;
}

    // محاكي
    public $sim = [
        'employee_id' => null,
        'sale' => 0.0,
        'cost' => 0.0,
        'method' => null,
        'collected' => 0.0,
        'result' => null,
    ];

  
    public function addEmpRateOverride()
    {
        $this->employeeRateOverridesRows[] = ['employee_id' => null, 'rate' => null];
    }
    public function removeEmpRateOverride($i)
    {
        unset($this->employeeRateOverridesRows[$i]);
        $this->employeeRateOverridesRows = array_values($this->employeeRateOverridesRows);
    }

    public function addCollectorOverride()
    {
        $this->collectorOverrideRows[] = [
            'employee_id' => null,
            'method' => 1,
            'type' => 'percent',
            'value' => 0,
            'basis' => 'collected_amount',
        ];
    }
    public function removeCollectorOverride($i)
    {
        unset($this->collectorOverrideRows[$i]);
        $this->collectorOverrideRows = array_values($this->collectorOverrideRows);
    }

    public function simulate()
    {
        $netMargin = max((float)$this->sim['sale'] - (float)$this->sim['cost'], 0);

        // نسبة الموظف الفعالة: override ثم الافتراضي
        $empId = $this->sim['employee_id'];
        $rateOverride = null;
        foreach ($this->employeeRateOverridesRows as $row) {
            if ((string)($row['employee_id'] ?? '') === (string)$empId && $row['rate'] !== null) {
                $rateOverride = (float)$row['rate']; break;
            }
        }
        $effectiveEmployeeRate = $rateOverride !== null ? $rateOverride : (float)$this->employeeRate;
        $employeeCommission = round($netMargin * $effectiveEmployeeRate / 100, 2);

        // قاعدة المُحصّل الفعالة: override إن وُجد للموظف+الطريقة وإلا العامة
        $rule = $this->collectorRules[$this->sim['method']] ?? null;
        foreach ($this->collectorOverrideRows as $row) {
            if ((string)($row['employee_id'] ?? '') === (string)$empId
                && (string)($row['method'] ?? '') === (string)$this->sim['method']) {
                $rule = $row; break;
            }
        }

        $basisValue = 0;
        if ($rule) {
            switch ($rule['basis']) {
                case 'net_margin': $basisValue = $netMargin; break;
                case 'employee_commission': $basisValue = $employeeCommission; break;
                default: $basisValue = (float)$this->sim['collected']; // collected_amount
            }

            if (($rule['type'] ?? 'percent') === 'fixed') {
                $collectorCommission = (float)$rule['value'];
            } else {
                $collectorCommission = round($basisValue * ((float)$rule['value'] / 100), 2);
            }
        } else {
            $collectorCommission = 0;
        }

        $companyShare = max($netMargin - $employeeCommission - $collectorCommission, 0);

        $this->sim['result'] = [
            'net_margin'           => $netMargin,
            'employee_commission'  => $employeeCommission,
            'collector_commission' => $collectorCommission,
            'company_share'        => $companyShare,
        ];
    }

   

    public function render()
    {
        $employees = User::where('agency_id', $this->agency->id)->select('id','name')->get();
        return view('livewire.agency.commission-policies', compact('employees'))
            ->layout('layouts.agency');
    }





public function mount()
{
    $this->agency = Auth::user()->agency;

    // تهيئة افتراضية
    foreach ($this->collectionMethods as $m => $label) {
        $this->collectorRules[$m] = ['type'=>'percent','value'=>0,'basis'=>'collected_amount'];
    }

    // تحميل بروفايل فعّال إن وُجد
    $profile = CommissionProfile::with(['collectorRules','employeeRateOverrides','collectorOverrides'])
        ->where('agency_id', $this->agency->id)
        ->where('is_active', true)
        ->first();

    if ($profile) {
        $this->employeeRate = (float)$profile->employee_rate;
        $this->daysToDebt   = (int)$profile->days_to_debt;
        $this->debtBehavior = $profile->debt_behavior;

        // قواعد المُحصّل العامة
        foreach ($profile->collectorRules as $r) {
            $this->collectorRules[$r->method] = [
                'type'  => $r->type,
                'value' => (float)$r->value,
                'basis' => $r->basis,
            ];
        }

        // نسب خاصة
        $this->employeeRateOverridesRows = $profile->employeeRateOverrides->map(function($r){
            return ['user_id' => $r->user_id, 'rate' => (float)$r->rate];
        })->values()->all();

        // قواعد مُحصّل خاصة
        $this->collectorOverrideRows = $profile->collectorOverrides->map(function($r){
            return [
                'user_id' => $r->user_id,
                'method'  => (int)$r->method,
                'type'    => $r->type,
                'value'   => (float)$r->value,
                'basis'   => $r->basis,
            ];
        })->values()->all();
    }
}

public function save()
{
    // تطبيع المفاتيح: دعم employee_id مؤقتًا
    $empRateRows = collect($this->employeeRateOverridesRows ?? [])
        ->map(fn($r)=>[
            'user_id' => $r['user_id'] ?? ($r['employee_id'] ?? null),
            'rate'    => $r['rate'] ?? null,
        ])
        ->filter(fn($r)=>$r['user_id'] && $r['rate'] !== null)
        ->values()
        ->all();

    $collectorOvRows = collect($this->collectorOverrideRows ?? [])
        ->map(fn($r)=>[
            'user_id' => $r['user_id'] ?? ($r['employee_id'] ?? null),
            'method'  => $r['method'] ?? null,
            'type'    => $r['type'] ?? null,
            'value'   => $r['value'] ?? null,
            'basis'   => $r['basis'] ?? null,
        ])
        ->filter(fn($r)=>$r['user_id'] && $r['method'])
        ->values()
        ->all();

    $this->validate([
        'employeeRate' => 'nullable|numeric|min:0',
        'daysToDebt'   => 'required|integer|min:0',
        'debtBehavior' => 'required|in:deduct_commission_until_paid,hold_commission',
        'collectorRules.*.type'  => 'nullable|in:percent,fixed',
        'collectorRules.*.value' => 'nullable|numeric|min:0',
        'collectorRules.*.basis' => 'nullable|in:collected_amount,net_margin,employee_commission',
    ]);

    DB::transaction(function () use ($empRateRows, $collectorOvRows) {
        // بروفايل الوكالة الفعّال
        $profile = CommissionProfile::updateOrCreate(
            ['agency_id'=>$this->agency->id, 'name'=>'الافتراضي'],
            [
                'is_active'      => true,
                'employee_rate'  => $this->employeeRate ?? 0,
                'days_to_debt'   => $this->daysToDebt,
                'debt_behavior'  => $this->debtBehavior,
            ]
        );

        // قواعد المُحصّل العامة
        // قواعد المُحصّل العامة
CommissionCollectorRule::where('profile_id', $profile->id)->delete();

$ruleRows = [];
foreach (($this->collectorRules ?? []) as $methodKey => $r) {
    $method = $this->normalizeMethodKey($methodKey);

    // حوّل القيم الفارغة إلى null
    $type  = ($r['type']  ?? null) === '' ? null : ($r['type']  ?? null);
    $basis = ($r['basis'] ?? null) === '' ? null : ($r['basis'] ?? null);
    $value = ($r['value'] ?? null);
    $value = ($value === '' || $value === null) ? null : (float)$value;

    // خيار 1: خزّن صف "بدون تحصيل" كـ NULL في method وقيمه null
    if ($method === null) {
        $ruleRows[] = [
            'profile_id' => $profile->id,
            'method'     => null,
            'type'       => null,
            'value'      => null,
            'basis'      => null,
            'created_at' => now(), 'updated_at' => now(),
        ];
        continue;
    }

    // خيار 2: لو تفضّل عدم تخزين صف "بدون تحصيل" احذف البلوك أعلاه واستبدله بـ:
    // if ($method === null) { continue; }

    $ruleRows[] = [
        'profile_id' => $profile->id,
        'method'     => $method,        // رقم صحيح
        'type'       => $type,          // percent/fixed أو null
        'value'      => $value,         // رقم أو null
        'basis'      => $basis,         // collected_amount/net_margin/employee_commission أو null
        'created_at' => now(), 'updated_at' => now(),
    ];
}

if ($ruleRows) {
    CommissionCollectorRule::insert($ruleRows);
}


        // نسب خاصة للمستخدمين
        CommissionEmployeeRateOverride::where('profile_id', $profile->id)->delete();
        if ($empRateRows) {
            CommissionEmployeeRateOverride::insert(array_map(fn($r)=>[
                'profile_id'=>$profile->id,'user_id'=>$r['user_id'],'rate'=>$r['rate'],
                'created_at'=>now(),'updated_at'=>now(),
            ], $empRateRows));
        }

        // قواعد مُحصّل خاصة
        CommissionCollectorOverride::where('profile_id', $profile->id)->delete();
        if ($collectorOvRows) {
            CommissionCollectorOverride::insert(array_map(fn($r)=>[
                'profile_id'=>$profile->id,'user_id'=>$r['user_id'],'method'=>$r['method'],
                'type'=>$r['type'],'value'=>$r['value'],'basis'=>$r['basis'],
                'created_at'=>now(),'updated_at'=>now(),
            ], $collectorOvRows));
        }
    });

    session()->flash('message', 'تم الحفظ في قاعدة البيانات بنجاح.');
}

}
