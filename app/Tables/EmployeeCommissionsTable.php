<?php

namespace App\Tables;

class EmployeeCommissionsTable
{
    public static function columns()
    {
        return [
            ['key' => 'user', 'label' => 'الموظف', 'sortable' => true],
            ['key' => 'target', 'label' => 'الهدف', 'format' => 'money', 'sortable' => true],
            ['key' => 'rate', 'label' => 'نسبة العمولة', 'sortable' => true],
            ['key' => 'total_profit', 'label' => 'الربح الكلي', 'format' => 'money', 'color' => 'primary-600', 'sortable' => true],
            ['key' => 'collected_profit', 'label' => 'الربح المحصل', 'format' => 'money', 'color' => 'primary-500', 'sortable' => true],
            ['key' => 'expected_commission', 'label' => 'العمولة المتوقعة', 'format' => 'money', 'color' => 'green-600', 'sortable' => true],
            ['key' => 'earned_commission', 'label' => 'العمولة المستحقة', 'format' => 'money', 'color' => 'blue-600', 'sortable' => true],
        ];
    }
}