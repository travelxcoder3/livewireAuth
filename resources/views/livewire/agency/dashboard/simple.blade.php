<!-- لوحة التحكم المبسطة للمستخدم العادي -->
@php $stats = $this->simpleStats; @endphp

<!-- إحصائيات أساسية -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                <i class="fas fa-users text-2xl"></i>
            </div>
            <div class="mr-4">
                <h3 class="text-lg font-semibold text-gray-800">{{ $stats['total_users'] }}</h3>
                <p class="text-gray-600">إجمالي المستخدمين</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
                <i class="fas fa-user-check text-2xl"></i>
            </div>
            <div class="mr-4">
                <h3 class="text-lg font-semibold text-gray-800">{{ $stats['active_users'] }}</h3>
                <p class="text-gray-600">المستخدمين النشطين</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                <i class="fas fa-building text-2xl"></i>
            </div>
            <div class="mr-4">
                <h3 class="text-lg font-semibold text-gray-800">{{ $stats['agency_info']->status }}</h3>
                <p class="text-gray-600">حالة الوكالة</p>
            </div>
        </div>
    </div>
</div>

<!-- معلومات الوكالة -->
<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">معلومات الوكالة</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <p class="text-gray-600">اسم الوكالة</p>
            <p class="font-semibold">{{ $stats['agency_info']->name }}</p>
        </div>
        <div>
            <p class="text-gray-600">البريد الإلكتروني</p>
            <p class="font-semibold">{{ $stats['agency_info']->email }}</p>
        </div>
        <div>
            <p class="text-gray-600">رقم الترخيص</p>
            <p class="font-semibold">{{ $stats['agency_info']->license_number }}</p>
        </div>
        <div>
            <p class="text-gray-600">تاريخ انتهاء الترخيص</p>
            <p class="font-semibold">{{ $stats['agency_info']->license_expiry_date->format('Y-m-d') }}</p>
        </div>
    </div>
</div>

<!-- رسالة ترحيب -->
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">مرحباً بك في النظام</h2>
    <div class="text-gray-600">
        <p class="mb-4">أنت الآن في لوحة التحكم المبسطة. يمكنك الوصول إلى الميزات المتاحة لك من خلال القائمة الجانبية.</p>
        <p class="mb-4">إذا كنت تحتاج إلى صلاحيات إضافية، يرجى التواصل مع مدير النظام.</p>
        <div class="bg-blue-50 border-r-4 border-blue-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-400"></i>
                </div>
                <div class="mr-3">
                    <p class="text-sm text-blue-700">
                        هذه لوحة التحكم المبسطة تظهر لك المعلومات الأساسية فقط. للوصول إلى ميزات إدارية متقدمة، تحتاج إلى صلاحيات إضافية.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div> 