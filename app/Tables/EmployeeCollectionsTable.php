<?php
namespace App\Tables;

class EmployeeCollectionsTable {
  public static function columns() {
    return [
      ['key'=>'index','label'=>'#'],
      ['key'=>'employee_name','label'=>'الموظف'],
      ['key'=>'remaining_total','label'=>' تحت التحصيل ','format'=>'money'],
      ['key'=>'last_payment_at','label'=>'تاريخ آخر سداد','format'=>'date'],
      ['key'=>'last_collection_amount','label'=>'مبلغ آخر تحصيل','format'=>'money'],
      [
        'key'=>'actions','label'=>'إجراء','actions'=>[[
          'type'=>'details','label'=>'تفاصيل','icon'=>'fas fa-eye',
          'can' => auth()->user()->can('collection.emp.details.view'),
          'url'=>fn($row)=>route('agency.employee-collections.show',$row->employee_id),
          'class'=>'text-[rgb(var(--primary-600))] hover:text-black font-medium text-xs'
        ]]
      ],
    ];
  }
}
