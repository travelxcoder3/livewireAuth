<div>
    <div class="flex flex-col h-screen overflow-hidden" style="overflow-x: auto; overflow-y: auto;">
            <x-toast />

        <!-- القسم العلوي الثابت -->
        <div class="flex-none p-4 bg-gray-50">
            <!-- عنوان الصفحة -->
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-black mb-2">إدارة الوكالات</h2>
                <p class="text-gray-700 text-sm">عرض وإدارة جميع الوكالات المسجلة في النظام</p>
            </div>

            <!-- بطاقة البحث والإجراءات -->
            <div class="bg-white rounded-xl shadow-md p-4">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <!-- حقل البحث -->
                    <div class="relative w-full md:w-1/3">
                        <input type="text" wire:model.live.debounce.500ms="search"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 pr-10 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-sm"
                            placeholder="ابحث عن وكالة...">
                        <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>

                    <!-- أزرار الإجراءات -->
                    <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                        @if (!$showAll)
                            <select wire:model="perPage"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400">
                                <option value="10">10 صفوف</option>
                                <option value="25">25 صف</option>
                                <option value="50">50 صف</option>
                                <option value="100">100 صف</option>
                            </select>
                        @endif

                       <x-primary-button
                            wire:click="toggleShowAll"
                            class="whitespace-nowrap"
                        >
                            {{ $showAll ? 'عرض الصفحات' : 'عرض الكل' }}
                        </x-primary-button>

                                                <!-- زر تغيير بيانات الادمن -->
                                            <x-primary-button
                            wire:click="$set('showPasswordModal', true)"
                            class="whitespace-nowrap"
                        >
                            تغيير بيانات الادمن
                        </x-primary-button>

                        <!-- مودال تغيير بيانات الادمن -->
                        @if ($showPasswordModal)
                           <div class="fixed inset-0 bg-black/30 backdrop-blur-sm flex justify-center items-start pt-24 z-50">
                                <div class="bg-white/90 rounded-lg p-6 w-full md:w-1/3 shadow-xl">
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-xl font-bold text-gray-900">تغيير  بيانات ادمن الوكالة</h3>
                                    </div>
                                    <form wire:submit.prevent="updatePassword">
                                        <!-- حقل اختيار الوكالة باستخدام select-field component -->
                                        <x-select-field
                                            wireModel="selectedAgencyId"
                                            :options="$agencies->pluck('name', 'id')->toArray()"
                                            placeholder="-- اختر وكالة --"
                                            required
                                            errorName="selectedAgencyId"
                                            class="mb-4"
                                        />

                                        
                                        <!-- معلومات مدير الوكالة -->
                                        @if ($selectedAgencyAdminName && $selectedAgencyAdminEmail)
                                            <div class="mb-4 text-sm text-gray-700 space-y-1">
                                                <div><span class="font-semibold">مدير الوكالة:</span> {{ $selectedAgencyAdminName }}</div>
                                                <div><span class="font-semibold">البريد الإلكتروني:</span> {{ $selectedAgencyAdminEmail }}</div>
                                            </div>
                                        @endif


                                        <!-- حقل كلمة المرور الجديدة باستخدام input-field component -->
                                        <x-input-field
                                            type="password"
                                            label="كلمة المرور الجديدة"
                                            wireModel="newPassword"
                                            required
                                            errorName="newPassword"
                                            class="mb-4"
                                        />

                                        <!-- حقل تأكيد كلمة المرور باستخدام input-field component -->
                                        <x-input-field
                                            type="password"
                                            label="تأكيد كلمة المرور الجديدة"
                                            wireModel="confirmPassword"
                                            required
                                            errorName="confirmPassword"
                                            class="mb-4"
                                        />

                                        <div class="flex justify-end gap-3 mt-4">
                                            <!-- زر الإلغاء -->
                                            <x-primary-button 
                                                type="button" 
                                                wire:click="$set('showPasswordModal', false)"
                                                color="white"
                                                textColor="gray-700"
                                                :gradient="false"
                                                class="border border-gray-300 hover:bg-gray-50"
                                            >
                                                إلغاء
                                            </x-primary-button>

                                            <!-- زر التحديث -->
                                            <x-primary-button 
                                                type="submit"
                                                textColor="white"
                                            >
                                                تحديث
                                            </x-primary-button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            @if (session('error'))
                                <div class="mb-4 p-3 bg-red-100 border border-red-200 text-red-800 rounded-lg text-sm">
                                    {{ session('error') }}
                                </div>
                            @endif
                        @endif

                  <x-primary-button
                    type="button"
                    color="primary"
                    gradient="true"
                    class="whitespace-nowrap"
                    @click="window.location.href='{{ route('admin.add-agency') }}'"
                >
                    إضافة وكالة جديدة
                </x-primary-button>

                    </div>
                </div>
            </div>

   

                @if (session('error'))
                    <div class="mt-4 p-3 bg-red-100 ...">
                        {{ session('error') }}
                    </div>
                @endif
                @if (isset($successMessage) && $successMessage)
                    <div class="mt-4 p-3 bg-emerald-100 ...">
                        {{ $successMessage }}
                    </div>
                @endif

        </div>

        <!-- القسم السفلي مع الجدول القابل للتمرير -->
        <div class="flex-1 overflow-auto px-4 pb-4 min-h-0">
    <div class="bg-white rounded-xl shadow-md h-full flex flex-col">
        <div class="flex-1 overflow-auto min-h-0">
            <div class="overflow-x-auto overflow-y-auto w-full max-h-[70vh]" style="scrollbar-width: thin;">

                        <table class="min-w-max divide-y divide-gray-200 text-xs text-right" style="min-width:2600px;">
                        <thead class="bg-gray-100 text-gray-900 sticky top-0 z-10">
                            <tr>
                                <th class="px-3 py-2 whitespace-nowrap">الشعار</th>
                                <th class="px-3 py-2 whitespace-nowrap text-gray-900" style="width:180px;">اسم الوكالة</th>
                                <th class="px-3 py-2 whitespace-nowrap" style="width:220px;">البريد الإلكتروني</th>
                                <th class="px-3 py-2 whitespace-nowrap" style="width:170px;">الهاتف</th>
                                <th class="px-3 py-2 whitespace-nowrap">العملة</th>
                                <th class="px-3 py-2 whitespace-nowrap" style="width:200px;">العنوان</th>
                                <th class="px-3 py-2 whitespace-nowrap">رقم الرخصة</th>
                                <th class="px-3 py-2 whitespace-nowrap">السجل التجاري</th>
                                <th class="px-3 py-2 whitespace-nowrap">الرقم الضريبي</th>
                                <th class="px-3 py-2 whitespace-nowrap">الوكالة الرئيسية</th>
                                <th class="px-3 py-2 whitespace-nowrap">الحاله</th>
                                <th class="px-3 py-2 whitespace-nowrap">بداية الاشتراك</th>
                                <th class="px-3 py-2 whitespace-nowrap">نهاية الاشتراك</th>
                                <th class="px-3 py-2 whitespace-nowrap">حالة الاشتراك</th> <!-- العمود الجديد -->
                                <th class="px-3 py-2 whitespace-nowrap">المستخدمين</th>
                                <th class="px-3 py-2 whitespace-nowrap" style="width:180px;">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($agencies as $agency)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2">
                                        @if ($agency->logo)
                                            <img src="{{ asset('storage/' . $agency->logo) }}"
                                                class="h-8 w-8 rounded-full object-cover" alt="شعار الوكالة">
                                        @else
                                            <div
                                                class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                                    </path>
                                                </svg>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 font-medium">{{ $agency->name }}</td>
                                    <td class="px-3 py-2">{{ $agency->email }}</td>
                                    <td class="px-3 py-2">
                                        <div>{{ $agency->phone }}</div>
                                        @if ($agency->landline)
                                            <div class="text-xs text-gray-500">{{ $agency->landline }}</div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">{{ $agency->currency }}</td>
                                    <td class="px-3 py-2 max-w-xs truncate">{{ $agency->address }}</td>
                                    <td class="px-3 py-2">{{ $agency->license_number }}</td>
                                    <td class="px-3 py-2">{{ $agency->commercial_record }}</td>
                                    <td class="px-3 py-2">{{ $agency->tax_number }}</td>
                                    <td class="px-3 py-2">
                                        @if ($agency->parent_id)
                                            {{ optional($agency->parent)->name ?? '—' }}
                                        @else
                                            <span class="text-xs text-gray-500">رئيسية</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">
                                        @php
                                            $isExpired =
                                                $agency->subscription_end_date &&
                                                $agency->subscription_end_date < now();
                                        @endphp
                                        @if ($isExpired)
                                            <span
                                                class="px-2 py-1 rounded-full bg-red-100 text-red-800 text-xs font-medium">
                                                غير نشطة
                                            </span>
                                        @elseif($agency->status == 'active')
                                            <span class="px-2 py-1 rounded-full text-xs font-medium"
                                                style="background-color: rgba(var(--primary-100), 0.3); color: rgb(var(--primary-700));">
                                                نشطة
                                            </span>
                                        @elseif($agency->status == 'inactive')
                                            <span
                                                class="px-2 py-1 rounded-full bg-yellow-100 text-yellow-800 text-xs font-medium">
                                                غير نشطة
                                            </span>
                                        @else
                                            <span
                                                class="px-2 py-1 rounded-full bg-red-100 text-red-800 text-xs font-medium">
                                                موقوفة
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">
                                        @if ($agency->subscription_start_date)
                                            <span
                                                class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 px-2 py-1 rounded-md">
                                                <svg class="h-4 w-4 text-emerald-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <rect x="3" y="4" width="18" height="18" rx="2"
                                                        stroke-width="2" />
                                                    <path stroke-width="2" d="M16 2v4M8 2v4M3 10h18" />
                                                </svg>
                                                {{ is_string($agency->subscription_start_date) ? $agency->subscription_start_date : $agency->subscription_start_date->format('Y-m-d') }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">
                                        @if ($agency->subscription_end_date)
                                            <span
                                                class="inline-flex items-center gap-1 bg-rose-50 text-rose-700 px-2 py-1 rounded-md">
                                                <svg class="h-4 w-4 text-rose-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <rect x="3" y="4" width="18" height="18" rx="2"
                                                        stroke-width="2" />
                                                    <path stroke-width="2" d="M16 2v4M8 2v4M3 10h18" />
                                                </svg>
                                                {{ is_string($agency->subscription_end_date) ? $agency->subscription_end_date : $agency->subscription_end_date->format('Y-m-d') }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">
                                        @if ($agency->subscription_end_date && $agency->subscription_end_date < now())
                                            <span
                                                class="px-2 py-1 rounded-full bg-red-100 text-red-800 text-xs font-medium">منتهي</span>
                                        @else
                                            <span class="px-2 py-1 rounded-full text-xs font-medium"
                                                style="background-color: rgba(var(--primary-100), 0.3); color: rgb(var(--primary-700));">
                                                مستمر
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-center">{{ $agency->max_users }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        <a href="{{ route('admin.edit-agency', $agency->id) }}"
                                            style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%); color: #fff;"
                                            class="px-3 py-1 rounded-lg font-medium text-xs transition duration-200 shadow hover:shadow-md whitespace-nowrap">
                                            تعديل
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="17" class="text-center py-4 text-gray-400">لا توجد وكالات مسجلة
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- الترقيم -->
                @if (!$showAll)
                    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                        {{ $agencies->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        html,
        body {
            height: 100%;
            overflow: auto;
        }
        .peer:placeholder-shown+label {
            top: 0.75rem;
            font-size: 0.875rem;
            color: #6b7280;
        }
        .peer:not(:placeholder-shown)+label,
        .peer:focus+label {
            top: -0.5rem;
            font-size: 0.75rem;
            color: #059669;
        }

        select:required:invalid {
            color: #6b7280;
        }

        select option {
            color: #111827;
        }

        /* تحسين عرض الجدول على الشاشات الصغيرة */
        @media (max-width: 1024px) {
            /* تم تعطيل التمرير الخاص فقط بالشاشات الصغيرة ليكون التمرير الأفقي دائمًا */
            /* table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            } */
        }

        /* إضافة خصائص التمرير للجدول */
        .overflow-y-auto {
            overflow-y: auto;
            overflow-x: auto;
        }

        /* تأكيد ظهور شريط التمرير */
        .overflow-y-auto::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .overflow-y-auto::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .overflow-y-auto::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .overflow-y-auto::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
</div>
