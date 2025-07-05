<div class="p-6">
    @if(session('message'))
        <div class="mb-4 p-3 bg-emerald-100 text-emerald-800 rounded-lg text-center">
            {{ session('message') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded-lg text-center">
            {{ session('error') }}
        </div>
    @endif
    @if(isset($successMessage) && $successMessage)
        <div class="mb-4 p-3 bg-emerald-100 text-emerald-800 rounded-lg text-center">
            {{ $successMessage }}
        </div>
    @endif
    
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-2">إدارة الوكلات</h2>
        <p class="text-gray-600">عرض وإدارة جميع الوكلات المسجلة في النظام</p>
    </div>

    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">قائمة الوكلات</h3>
                <a href="{{ route('admin.add-agency') }}" 
                   class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg transition duration-200">
                    إضافة وكالة جديدة
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            اسم الوكالة
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            البريد الإلكتروني
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            الهاتف
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            مدير الوكالة
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            تاريخ الإنشاء
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            الإجراءات
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($agencies as $agency)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $agency->name }}</div>
                                <div class="text-sm text-gray-500">{{ $agency->address }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $agency->email }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $agency->phone }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($agency->admin)
                                    <div class="text-sm font-medium text-gray-900">{{ $agency->admin->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $agency->admin->email }}</div>
                                @else
                                    <span class="text-sm text-red-500">لم يتم تعيين مدير</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $agency->created_at->format('Y-m-d') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex flex-row items-center justify-center gap-8">
                                    <a href="{{ route('admin.edit-agency', $agency->id) }}"
                                       class="transition px-4 py-1 rounded-lg font-medium text-emerald-600 hover:text-white hover:bg-emerald-500 border border-emerald-100 hover:border-emerald-500">
                                        تعديل
                                    </a>
                                    <a href="{{ route('admin.delete-agency', $agency->id) }}"
                                       class="transition px-4 py-1 rounded-lg font-medium text-red-600 hover:text-white hover:bg-red-500 border border-red-100 hover:border-red-500">
                                        حذف
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                لا توجد وكلات مسجلة حالياً
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- مودال التعديل --}}
@if($showEditModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center min-h-screen bg-black/40">
        <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-2xl mx-4 relative animate-fade-in-up border-t-4 border-emerald-500">
            <button wire:click="$set('showEditModal', false)" class="absolute top-4 left-4 text-gray-400 hover:text-red-500 text-2xl font-bold">&times;</button>
            <h3 class="text-2xl font-bold mb-6 text-emerald-600 text-center">تعديل بيانات الوكالة</h3>
            <form wire:submit.prevent="saveEdit" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">اسم الوكالة</label>
                        <input type="text" wire:model.defer="agency_name" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400" />
                        @error('agency_name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">عدد المستخدمين المسموحين</label>
                        <input type="number" min="1" wire:model.defer="max_users" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400" />
                        @error('max_users') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">البريد الإلكتروني للوكالة</label>
                        <input type="email" wire:model.defer="agency_email" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400" />
                        @error('agency_email') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">رقم الهاتف</label>
                        <input type="text" wire:model.defer="agency_phone" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400" />
                        @error('agency_phone') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">العنوان</label>
                        <input type="text" wire:model.defer="agency_address" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400" />
                        @error('agency_address') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">رقم الرخصة</label>
                        <input type="text" wire:model.defer="license_number" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400" />
                        @error('license_number') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">السجل التجاري</label>
                        <input type="text" wire:model.defer="commercial_record" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400" />
                        @error('commercial_record') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">الرقم الضريبي</label>
                        <input type="text" wire:model.defer="tax_number" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400" />
                        @error('tax_number') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">تاريخ انتهاء الرخصة</label>
                        <input type="date" wire:model.defer="license_expiry_date" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400" />
                        @error('license_expiry_date') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">وصف الوكالة (اختياري)</label>
                    <textarea wire:model.defer="description" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400"></textarea>
                    @error('description') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div class="mt-8 flex justify-center gap-4">
                    <button type="submit" class="bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white px-8 py-3 rounded-lg font-bold text-lg transition duration-200 shadow-lg hover:shadow-xl">حفظ التعديلات</button>
                    <button type="button" wire:click="$set('showEditModal', false)" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-8 py-3 rounded-lg font-bold text-lg transition duration-200">إلغاء</button>
                </div>
            </form>
        </div>
    </div>
@endif

{{-- مودال تأكيد الحذف --}}
@if($showDeleteModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center min-h-screen bg-black/40">
        <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md mx-4 relative animate-fade-in-up border-t-4 border-red-500">
            <button wire:click="cancelDelete" class="absolute top-4 left-4 text-gray-400 hover:text-red-500 text-2xl font-bold">&times;</button>
            <h3 class="text-2xl font-bold mb-6 text-red-600 text-center">تأكيد حذف الوكالة</h3>
            <p class="text-center text-gray-700 mb-8">هل أنت متأكد أنك تريد حذف هذه الوكالة؟ لا يمكن التراجع عن هذا الإجراء.</p>
            <div class="flex justify-center gap-4">
                <button wire:click="confirmDelete" class="bg-red-600 hover:bg-red-700 text-white px-8 py-3 rounded-lg font-bold text-lg transition duration-200 shadow-lg hover:shadow-xl">تأكيد الحذف</button>
                <button wire:click="cancelDelete" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-8 py-3 rounded-lg font-bold text-lg transition duration-200">إلغاء</button>
            </div>
        </div>
    </div>
@endif
