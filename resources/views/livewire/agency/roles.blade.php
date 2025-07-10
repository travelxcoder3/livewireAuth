<div class="space-y-6">
    <!-- العنوان + زر الإضافة -->
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold" style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            إدارة الأدوار والصلاحيات
        </h2>
        @can('roles.create')
        <button wire:click="$set('showForm', true)" 
                class="text-white font-bold px-4 py-2 rounded-xl shadow-md transition duration-300 text-sm"
                style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
            + إضافة دور جديد
        </button>
        @endcan
    </div>

    <!-- حقل البحث -->
    <div class="bg-white rounded-xl shadow-md p-4">
        <div class="relative mt-1">
            <input wire:model.live="search" 
                   type="text" 
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs"
                   placeholder=" ">
            <label class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500">
                بحث في الأدوار
            </label>
        </div>
    </div>

    <!-- جدول الأدوار -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-xs text-right">
            <thead class="bg-gray-100 text-gray-600">
                <tr>
                    <th class="px-3 py-2">اسم الدور</th>
                    <!-- تم حذف عمود الوصف -->
                    <th class="px-3 py-2">الصلاحيات</th>
                    <th class="px-3 py-2">عدد المستخدمين</th>
                    <th class="px-3 py-2">تاريخ الإنشاء</th>
                    <th class="px-3 py-2">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($roles as $role)
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-sm">{{ $role->display_name ?? $role->name }}</span>
                            @if(in_array($role->name, ['super-admin', 'agency-admin']))
                                <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[10px] font-semibold">
                                    أساسي
                                </span>
                            @endif
                        </div>
                        <div class="text-gray-500 text-xs">{{ $role->name }}</div>
                    </td>
                    <!-- تم حذف خلية الوصف -->
                    <td class="px-3 py-2">
                        <div class="flex flex-wrap gap-1">
                            @foreach(array_slice($role->permissions->pluck('name')->toArray(), 0, 3) as $perm)
                                <span class="px-2 py-0.5 rounded-full bg-[rgba(var(--primary-100),0.5)] text-[10px] font-medium border border-[rgba(var(--primary-200),0.5)] text-[rgb(var(--primary-700))]">
                                    {{ $perm }}
                                </span>
                            @endforeach
                            @if($role->permissions->count() > 3)
                                <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 text-[10px]">+{{ $role->permissions->count() - 3 }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-3 py-2 text-center">{{ $role->users_count ?? 0 }}</td>
                    <td class="px-3 py-2">{{ $role->created_at->format('Y-m-d') }}</td>
                    <td class="px-3 py-2">
                        <div class="flex gap-2">
                            @can('roles.edit')
                            <button wire:click="editRole({{ $role->id }})" class="text-sm text-[rgb(var(--primary-600))] hover:underline">تعديل</button>
                            @endcan

                            @can('roles.delete')
                            @if(!in_array($role->name, ['super-admin', 'agency-admin']))
                            <button wire:click="deleteRole({{ $role->id }})"
                                    onclick="return confirm('هل أنت متأكد من حذف هذا الدور؟')"
                                    class="text-sm text-red-600 hover:underline">حذف</button>
                            @endif
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-gray-400">لا توجد أدوار حالياً</td>
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

    <!-- نافذة إضافة/تعديل -->
    @if($showForm)
    <div class="fixed inset-0 z-50 bg-black/10 flex items-center justify-center backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-xl mx-4 p-6 relative">
            <button wire:click="$set('showForm', false)"
                    class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold">&times;</button>

            <h3 class="text-xl font-bold mb-4 text-center text-[rgb(var(--primary-700))]">
                {{ $editingRole ? 'تعديل الدور' : 'إضافة دور جديد' }}
            </h3>

            <form wire:submit.prevent="{{ $editingRole ? 'updateRole' : 'addRole' }}" class="space-y-4 text-sm">
                <!-- اسم الدور -->
                <div class="relative">
                    <input wire:model.defer="name" type="text"
                           class="w-full peer border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:outline-none"
                           placeholder=" " />
                    <label class="absolute right-3 top-2 text-xs text-gray-500 transition-all peer-focus:-top-2 peer-focus:text-[rgb(var(--primary-600))] bg-white px-1">
                        اسم الدور
                    </label>
                    @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- الصلاحيات -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-2">الصلاحيات</label>
                    <button type="button" wire:click="$toggle('showPermissions')"
                        class="mb-3 bg-[rgb(var(--primary-500))] hover:bg-[rgb(var(--primary-600))] text-white px-4 py-1 rounded-lg shadow text-xs font-bold transition">
                        {{ $showPermissions ? 'إخفاء الصلاحيات' : 'عرض الصلاحيات' }}
                    </button>
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
        <button type="button" wire:click="toggleModule('{{ $module }}')"
            class="w-full text-center px-2 py-1.5 rounded-md text-xs font-bold transition border border-theme
                @if(!empty($openModules[$module])) bg-[rgb(var(--primary-500))] text-white shadow @else bg-white text-[rgb(var(--primary-600))] @endif
                hover:bg-[rgb(var(--primary-100))]">
            <i class="fas {{ $iconMap[$module] ?? $iconMap['default'] }} mr-1 text-xs"></i>
            {{ __(ucfirst($module)) }}
        </button>
    @endforeach
</div>

<!-- زر الإلغاء والإضافة -->
<div class="flex justify-end gap-3 mt-6">
    <button type="button" wire:click="$set('showForm', false)"
            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition text-sm">إلغاء</button>

    <button type="submit"
            class="text-white font-bold px-4 py-2 rounded-xl shadow-md transition text-sm"
            style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
        {{ $editingRole ? 'تحديث' : 'إضافة' }}
    </button>
</div>



<!-- لوحات الصلاحيات تظهر يسار نافذة الإضافة مباشرة -->
<div class="absolute top-0 right-[calc(100%+0.75rem)] z-40 space-y-3"
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

            <!-- قائمة الصلاحيات -->
            <div class="space-y-2 text-xs">
                @foreach($perms as $perm)
                    <label class="flex items-center justify-between border-b pb-1">
                        <span class="flex items-center gap-2 text-gray-700 font-medium">
                            <i class="fas fa-check-circle text-[rgb(var(--primary-500))] text-sm"></i>
                            {{ $perm->name }}
                        </span>
                        <input type="checkbox" wire:model="selectedPermissions" value="{{ $perm->name }}"
                               class="h-4 w-4 text-[rgb(var(--primary-600))] border-gray-300 rounded focus:ring-[rgb(var(--primary-500))]" />
                    </label>
                @endforeach
            </div>
        </div>
        @endif
    @endforeach
</div>
</div>

@endif

                    @error('selectedPermissions') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>

            </form>
        </div>
    </div>
    @endif

    <!-- رسالة نجاح -->
    @if(session()->has('message'))
    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" x-show="show" x-transition
         class="fixed bottom-4 right-4 bg-[rgb(var(--primary-500))] text-white px-4 py-2 rounded shadow text-sm">
        {{ session('message') }}
    </div>
    @endif
</div>
