<div class="space-y-6">
    <!-- رسائل النجاح -->
    @if (session()->has('message'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" x-show="show" x-transition
             class="fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg text-sm z-50">
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle"></i>
                {{ session('message') }}
            </div>
        </div>
    @endif

    <!-- رسائل الخطأ -->
    @if (session()->has('error'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" x-show="show" x-transition
             class="fixed bottom-4 right-4 bg-red-500 text-white px-4 py-2 rounded-lg shadow-lg text-sm z-50">
            <div class="flex items-center gap-2">
                <i class="fas fa-exclamation-circle"></i>
                {{ session('error') }}
            </div>
        </div>
    @endif

    <!-- العنوان + زر الإضافة -->
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold" style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            إدارة الأدوار والصلاحيات
        </h2>
        @can('roles.create')
        <x-primary-button wire:click="$set('showForm', true)">
            + إضافة دور جديد
        </x-primary-button>
        @endcan
    </div>

    <!-- حقل البحث -->
    <div class="bg-white rounded-xl shadow-md p-4">
        <x-input-field 
            wireModel="search"
            label="بحث في الأدوار"
            placeholder=" "
            icon="fas fa-search"
        />
    </div>

    <!-- جدول الأدوار -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-xs text-right">
            <thead class="bg-[rgb(var(--primary-50))] text-[rgb(var(--primary-700))]">
                <tr>
                    <th class="px-3 py-2">اسم الدور</th>
                    <th class="px-3 py-2">الصلاحيات</th>
                    <th class="px-3 py-2">عدد المستخدمين</th>
                    <th class="px-3 py-2">تاريخ الإنشاء</th>
                    <th class="px-3 py-2">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($roles as $role)
                <tr class="hover:bg-[rgb(var(--primary-50))] transition duration-200">
                    <td class="px-3 py-2">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-sm">{{ $role->display_name ?? $role->name }}</span>
                            @if(in_array($role->name, ['super-admin', 'agency-admin']))
                                <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[10px] font-semibold border border-red-200 shadow-sm">
                                    <i class="fas fa-shield-alt ml-1"></i>
                                    أساسي
                                </span>
                            @endif
                        </div>
                        <div class="text-gray-500 text-xs">{{ $role->name }}</div>
                    </td>
                    <!-- زر عرض الصلاحيات -->
                    <td class="px-3 py-2 text-center">
                        <x-primary-button 
                            wire:click="showRolePermissions({{ $role->id }})"
                            color="primary-100"
                            textColor="primary-700"
                            padding="px-2 py-1"
                            fontSize="text-xs"
                            rounded="rounded-lg"
                            gradient="false"
                        >
                            <i class="fas fa-eye ml-1"></i>
                            عرض
                        </x-primary-button>
                    </td>

                    <td class="px-3 py-2 text-center">{{ $role->users_count ?? 0 }}</td>
                    <td class="px-3 py-2">{{ $role->created_at->format('Y-m-d') }}</td>
                    <td class="px-3 py-2">
                        <div class="flex gap-2">
                            @can('roles.edit')
                                @if ($role->name !== 'agency-admin')
                            <button wire:click="editRole({{ $role->id }})"
                                class="text-xs font-medium flex items-center gap-1"
                                style="color: rgb(var(--primary-600));">
                                <i class="fa fa-edit"></i>
                                تعديل
                            </button>
                                @endif
                            @endcan

                            @can('roles.delete')
                                @if (!in_array($role->name, ['super-admin', 'agency-admin']))
                                    <button wire:click="deleteRole({{ $role->id }})"
                                        onclick="return confirm('هل أنت متأكد من حذف هذا الدور؟')"
                                        class="text-xs font-medium text-red-600 hover:text-red-800">
                                        حذف
                                    </button>
                                @endif
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-gray-400">لا توجد أدوار حالياً</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
                   
        @if($roles->hasPages())
        <div class="px-4 py-2 border-t border-gray-200">
            {{ $roles->links() }}
        </div>
        @endif
    </div>

    <!-- النافذة المنبثقة لعرض الصلاحيات -->
    @if ($showPermissionsModal)
    <div class="fixed inset-0 z-50 bg-white/20 backdrop-blur-sm flex items-start justify-center pt-24">
        <div class="bg-white w-full max-w-md rounded-xl shadow-lg p-6 space-y-4 text-sm">
            <h3 class="text-lg font-bold border-b pb-2 text-gray-700">
                الصلاحيات الخاصة بـ: {{ $selectedRoleName }}
            </h3>

           <!-- عداد الصلاحيات -->
           <div style="background: rgba(var(--primary-100), 0.15); border: 1px solid rgb(var(--primary-200));"
                class="text-[rgb(var(--primary-700))] px-3 py-2 rounded-lg text-sm font-bold">
                <i class="fas fa-check-circle mr-1"></i>
                {{ count($selectedRolePermissions) }} صلاحية
            </div>


            <div class="flex flex-wrap gap-2 max-h-60 overflow-y-auto">
                @if(count($selectedRolePermissions) > 0)
                    @foreach ($selectedRolePermissions as $permission)
                    <span
                        style="background: rgba(var(--primary-100), 0.15); border: 1px solid rgb(var(--primary-200)); color: rgb(var(--primary-700));"
                        class="px-3 py-1 rounded-full text-xs font-medium">
                        {{ $permission }}
                    </span>
                    @endforeach
                @else
                    <span class="text-gray-500 text-sm">لا توجد صلاحيات لهذا الدور</span>
                @endif
            </div>

            <div class="text-end pt-4">
                <x-primary-button 
                    wire:click="$set('showPermissionsModal', false)"
                    color="white"
                    textColor="primary-700"
                    padding="px-4 py-1"
                    fontSize="text-xs"
                    rounded="rounded"
                    gradient="false"
                    :border="true"
                >
                    إغلاق
                </x-primary-button>
            </div>
        </div>
    </div>
    @endif

    <!-- نافذة إضافة/تعديل -->
    @if($showForm)
    <div class="fixed inset-0 z-50 bg-black/10 flex items-start justify-center pt-8 backdrop-blur-sm p-4">
        <!-- حاوية المودال المعدلة مع الحفاظ على التصميم الأصلي -->
        <div class="relative w-full max-w-3xl bg-white rounded-xl shadow-xl mx-4 overflow-visible"
             style="max-height: 90vh; display: flex; flex-direction: column;">
             
            <!-- زر الإغلاق الأصلي -->
            <button wire:click="closeForm"
                    class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold z-10">
                &times;
            </button>

            <!-- المحتوى القابل للتمرير مع الحفاظ على التنسيق الأصلي -->
            <div class="overflow-y-auto p-6">
                <!-- العنوان الأصلي -->
                <h3 class="text-xl font-bold mb-4 text-center text-[rgb(var(--primary-700))]">
                    {{ $editingRole ? 'تعديل الدور' : 'إضافة دور جديد' }}
                </h3>

                <form wire:submit.prevent="{{ $editingRole ? 'updateRole' : 'addRole' }}" class="space-y-4 text-sm">
                    <!-- اسم الدور -->
                    <x-input-field 
                        wireModel="name"
                        label="اسم الدور"
                        placeholder=" "
                        errorName="name"
                    />

                    <!-- قسم الصلاحيات مع تعديلات طفيفة للحفاظ على التنسيق -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-2">الصلاحيات</label>
                        
                        <!-- أزرار التحكم -->
                        <div class="flex flex-wrap gap-2 mb-3">
                           

                             <x-primary-button 
                                                type="button"
                                               wire:click="selectAllPermissions"
                                                textColor="white"
                                                padding="px-2 py-1"
                                                fontSize="text-xs"
                                                rounded="rounded-lg"
                                                :border="true"
                                                rounded="rounded-lg"
                                            >
                                                <i class="fas fa-check ml-1"></i>
                                                اختيار الكل
                                            </x-primary-button>

                            
                           <x-primary-button 
                                type="button"
                                wire:click="deselectAllPermissions"
                                color="white"
                                textColor="primary-700"
                                :border="true"
                                padding="px-3 py-2"
                                fontSize="text-xs"
                                rounded="rounded-lg"
                                gradient="false"
                            >
                                <i class="fas fa-times ml-1"></i>
                                إلغاء الكل
                            </x-primary-button>

                            
                            <x-primary-button 
                                type="button"
                                wire:click="$toggle('showPermissions')"
                                padding="px-4 py-2"
                                fontSize="text-xs"
                                rounded="rounded-lg"
                            >
                                <i class="fas {{ $showPermissions ? 'fa-eye-slash' : 'fa-eye' }} ml-1"></i>
                                {{ $showPermissions ? 'إخفاء الصلاحيات' : 'عرض الصلاحيات' }}
                            </x-primary-button>
                            
                            <!-- عداد الصلاحيات المختارة -->
                            <div class="bg-[rgb(var(--primary-100))] text-[rgb(var(--primary-700))] px-3 py-2 rounded-lg text-xs font-bold border border-[rgb(var(--primary-200))]">
                                <i class="fas fa-check-circle ml-1"></i>
                                {{ count($selectedPermissions) }} صلاحية مختارة
                            </div>
                        </div>
                        
                        @if($showPermissions)
                        @php
                            $iconMap = [
                                'users' => 'fa-user-friends',
                                'roles' => 'fa-user-shield',
                                'permissions' => 'fa-key',
                                'service_types' => 'fa-cogs',
                                'employees' => 'fa-id-badge',
                                'reports' => 'fa-chart-bar',
                                'settings' => 'fa-cog',
                                'customers' => 'fa-users',
                                'sales' => 'fa-cash-register',
                                'departments' => 'fa-building',
                                'branches' => 'fa-code-branch',
                                'providers' => 'fa-truck',
                                'collections' => 'fa-archive',
                                'dynamic_lists' => 'fa-list',
                                'intermediaries' => 'fa-user-tie',
                                'default' => 'fa-layer-group',
                            ];
                            $grouped = $permissions->groupBy(function($item) {
                                return explode('.', $item->name)[0] ?? 'أخرى';
                            });
                            $openModules = $openModules ?? [];
                        @endphp

                        <!-- شبكة الأقسام -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-2 mb-3">
                            @foreach($grouped as $module => $perms)
                                @php
                                    $modulePermissions = $perms->pluck('name')->toArray();
                                    $selectedModulePermissions = array_intersect($selectedPermissions, $modulePermissions);
                                    $selectedCount = count($selectedModulePermissions);
                                    $totalCount = count($modulePermissions);
                                    $isFullySelected = $this->isModuleFullySelected($module);
                                    $isPartiallySelected = $this->isModulePartiallySelected($module);
                                @endphp
                                <button type="button" wire:click="toggleModule('{{ $module }}')"
                                    class="w-full text-center px-3 py-2 rounded-lg text-xs font-bold transition duration-200 transform hover:scale-105 border-2 relative
                                        @if(!empty($openModules[$module])) 
                                            @if($isFullySelected) bg-green-500 text-white border-green-600 shadow-lg @else bg-[rgb(var(--primary-500))] text-white border-[rgb(var(--primary-600))] shadow-lg @endif
                                        @else 
                                            @if($isFullySelected) bg-green-100 text-green-700 border-green-300 shadow-md @elseif($isPartiallySelected) bg-yellow-100 text-yellow-700 border-yellow-300 shadow-md @else bg-white text-[rgb(var(--primary-600))] border-[rgb(var(--primary-300))] hover:border-[rgb(var(--primary-500))] @endif
                                        @endif">
                                    <i class="fas {{ $iconMap[$module] ?? $iconMap['default'] }} ml-1 text-xs"></i>
                                    {{ __(ucfirst($module)) }}
                                    
                                    @if($selectedCount > 0)
                                        <div class="absolute -top-2 -right-2 bg-red-500 text-white text-[10px] rounded-full w-5 h-5 flex items-center justify-center font-bold shadow-md">
                                            {{ $selectedCount }}
                                        </div>
                                    @endif
                                </button>
                            @endforeach
                        </div>

                        <!-- لوحات الصلاحيات -->
                        <div class="absolute top-0 right-[calc(100%+0.75rem)] z-40 space-y-3 max-h-[80vh] overflow-y-auto"
                             wire:ignore.self>
                            @foreach($grouped as $module => $perms)
                                @if(!empty($openModules[$module]))
                                <div class="bg-white border border-gray-200 rounded-xl shadow p-4 w-[300px]">
                                    <!-- رأس -->
                                    <div class="flex justify-between items-center mb-2">
                                        <div class="flex items-center gap-2 font-bold text-[rgb(var(--primary-700))] text-sm">
                                            <i class="fas {{ $iconMap[$module] ?? $iconMap['default'] }} text-[rgb(var(--primary-500))]"></i>
                                            {{ __(ucfirst($module)) }}
                                        </div>
                                        <button type="button" wire:click="toggleModule('{{ $module }}')"
                                                class="text-gray-500 hover:text-red-500 text-lg font-bold">&times;</button>
                                    </div>

                                    <!-- أزرار اختيار الكل للقسم -->
                                    <div class="flex gap-1 mb-3">
                                        @php
                                            $modulePermissions = $perms->pluck('name')->toArray();
                                            $selectedModulePermissions = array_intersect($selectedPermissions, $modulePermissions);
                                            $selectedCount = count($selectedModulePermissions);
                                            $totalCount = count($modulePermissions);
                                        @endphp
                                        
                                        <!-- عداد الصلاحيات المختارة في القسم -->
                                        <div class="bg-[rgb(var(--primary-100))] text-[rgb(var(--primary-700))] px-2 py-1 rounded-lg text-xs font-medium border border-[rgb(var(--primary-200))]">
                                            <i class="fas fa-check-circle ml-1"></i>
                                            {{ $selectedCount }}/{{ $totalCount }}
                                        </div>
                                        
                                        @if($this->isModuleFullySelected($module))
                                            <x-primary-button 
                                                type="button"
                                                wire:click="deselectAllModulePermissions('{{ $module }}')"
                                                color="red-500"
                                                textColor="black"
                                                padding="px-2 py-1"
                                                fontSize="text-xs"
                                                rounded="rounded-lg"
                                            >
                                                <i class="fas fa-times ml-1"></i>
                                                إلغاء الكل
                                            </x-primary-button>
                                        @elseif($this->isModulePartiallySelected($module))
                                           <x-primary-button 
                                                type="button"
                                                wire:click="selectAllModulePermissions('{{ $module }}')"
                                               color="red-500"
                                                textColor="white"
                                                padding="px-2 py-1"
                                                fontSize="text-xs"
                                                rounded="rounded-lg"
                                                :border="true"
                                                rounded="rounded-lg"
                                                gradient="false"
                                            >
                                                <i class="fas fa-check ml-1 text-[rgb(var(--primary-700))]"></i>
                                                اختيار الكل
                                            </x-primary-button>

                                            <x-primary-button 
                                                type="button"
                                                wire:click="deselectAllModulePermissions('{{ $module }}')"
                                                color="red-500"
                                                textColor="black"
                                                padding="px-2 py-1"
                                                fontSize="text-xs"
                                                rounded="rounded-lg"
                                            >
                                                <i class="fas fa-times ml-1"></i>
                                                إلغاء الكل
                                            </x-primary-button>
                                        @else
                                            <x-primary-button 
                                                type="button"
                                                wire:click="selectAllModulePermissions('{{ $module }}')"
                                                textColor="white"
                                                padding="px-2 py-1"
                                                fontSize="text-xs"
                                                rounded="rounded-lg"
                                                :border="true"
                                                rounded="rounded-lg"
                                                gradient="false"
                                            >
                                                <i class="fas fa-check ml-1"></i>
                                                اختيار الكل
                                            </x-primary-button>
                                        @endif
                                    </div>

                                    <!-- قائمة الصلاحيات -->
                                    <div class="space-y-2 text-xs max-h-60 overflow-y-auto">
                                        @foreach($perms as $perm)
                                            @php
                                                $isSelected = in_array($perm->name, $selectedPermissions);
                                            @endphp
                                            <label class="flex items-center justify-between border-b pb-1 hover:bg-gray-50 p-1 rounded transition
                                                @if($isSelected) bg-green-50 border-green-200 @endif">
                                                <span class="flex items-center gap-2 text-gray-700 font-medium">
                                                    <i class="fas {{ $isSelected ? 'fa-check-circle text-green-500' : 'fa-circle text-gray-300' }} text-sm"></i>
                                                    {{ $perm->name }}
                                                </span>
                                                <input type="checkbox" wire:model="selectedPermissions" value="{{ $perm->name }}"
                                                       class="h-4 w-4 border-gray-300 rounded accent-[rgb(var(--primary-500))] focus:ring-2 focus:ring-[rgb(var(--primary-500))] transition" />
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            @endforeach
                        </div>
                        @endif
                        @error('selectedPermissions') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- زر الإلغاء والإضافة -->
                    <div class="flex justify-end gap-3 mt-6">
                        <x-primary-button 
                            type="button"
                            wire:click="closeForm"
                            color="gray-200"
                            textColor="gray-800"
                            padding="px-4 py-2"
                            fontSize="text-sm"
                            rounded="rounded-xl"
                            gradient="false"
                        >
                            <i class="fas fa-times ml-1"></i>
                            إلغاء
                        </x-primary-button>

                        <x-primary-button 
                            type="submit"
                            padding="px-4 py-2"
                            fontSize="text-sm"
                            rounded="rounded-xl"
                        >
                            <i class="fas {{ $editingRole ? 'fa-save' : 'fa-plus' }} ml-1"></i>
                            {{ $editingRole ? 'تحديث' : 'إضافة' }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>