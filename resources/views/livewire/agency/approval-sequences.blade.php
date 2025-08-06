@php
    use App\Tables\ApprovalSequenceTable;
    $columns = ApprovalSequenceTable::columns();

    $fieldClass =
        'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer';
    $labelClass =
        'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]';
    $containerClass = 'relative mt-1';
@endphp

<div x-data="sequenceManager()" x-init="init()" class="p-6 bg-gray-50 min-h-screen">

    <!-- عنوان الصفحة -->
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-[rgb(var(--primary-700))]">تسلسل الموافقات</h1>

        @can('sequences.create')
            <button
                class="text-white font-bold px-4 py-2 rounded-xl shadow-md hover:shadow-xl transition duration-300 text-sm"
                style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);"
                @click="$wire.openAddModal()">
                + إضافة تسلسل جديد
            </button>
        @endcan
    </div>

    <!-- جدول التسلسلات -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200">
        <div class="p-4">
            <table class="min-w-full divide-y divide-gray-200 text-sm text-right">
                <thead class="bg-gray-100 text-[rgb(var(--primary-600))]">
                    <tr>
                        <th scope="col" class="px-6 py-3 font-bold text-sm text-right whitespace-nowrap">اسم التسلسل
                        </th>
                        <th scope="col" class="px-6 py-3 font-bold text-sm text-right whitespace-nowrap">نوع الإجراء
                        </th>
                        <th scope="col" class="px-6 py-3 font-bold text-sm text-center whitespace-nowrap">الإجراءات
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach ($sequences as $sequence)
                        <tr>
                            <td class="px-4 py-3 text-gray-800">{{ $sequence->name }}</td>
                            <td class="px-4 py-3 text-gray-800">{{ $sequence->action_type }}</td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex justify-center items-center gap-3">
                                    <button wire:click="viewSequence({{ $sequence->id }})"
                                        class="text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-700))] font-semibold text-sm transition flex items-center">
                                        <i class="fa fa-eye ml-1"></i> عرض
                                    </button>
                                    <span class="text-gray-300">|</span>
                                    <button wire:click="editSequence({{ $sequence->id }})"
                                        class="text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-700))] font-semibold text-sm transition flex items-center">
                                        <i class="fa fa-edit ml-1"></i> تعديل
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>


    <!-- مودال العرض -->
    <div x-show="openViewModal" x-cloak x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">

        <div @click.outside="openViewModal = false"
            class="relative bg-white rounded-xl shadow-xl p-6 max-w-lg w-full border border-gray-200 transform transition-all duration-300"
            :class="openViewModal ? 'opacity-100 scale-100' : 'opacity-0 scale-95'">

            <h2 class="text-xl font-bold text-[rgb(var(--primary-700))] mb-4 border-b pb-3">عرض تسلسل الموافقات</h2>

            <!-- 🟦 معلومات عامة داخل مربع -->
            <div
                class="bg-[rgba(var(--primary-50),0.5)] border border-[rgb(var(--primary-200))] rounded-xl p-4 mb-5 space-y-4 shadow-sm">
                <div>
                    <p class="text-sm text-gray-600 font-semibold mb-1">اسم التسلسل:</p>
                    <p class="text-sm text-[rgb(var(--primary-700))] font-bold">{{ $viewName }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-600 font-semibold mb-1">نوع الإجراء:</p>
                    <p class="text-sm text-[rgb(var(--primary-700))] font-bold">{{ $viewActionType }}</p>
                </div>
            </div>

            <!-- مسار الموافقة -->
            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                <p class="text-sm font-semibold text-gray-600 mb-3">مسار الموافقة:</p>
                <div class="flex items-center justify-start gap-2 flex-nowrap overflow-x-auto pb-2">
                    @foreach ($viewApprovers as $index => $approver)
                        <div class="flex items-center space-x-2 rtl:space-x-reverse select-none shrink-0">
                            <span
                                class="bg-[rgba(var(--primary-100),0.3)] px-4 py-2 rounded-lg text-sm font-medium text-[rgb(var(--primary-700))] shadow-sm border border-[rgb(var(--primary-200))]">
                                {{ $approver }}
                            </span>
                            @if (!$loop->last)
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 rotate-180"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- زر العودة -->
            <div class="mt-6 flex justify-end">
                <button @click="openViewModal = false"
                    class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-xl font-semibold shadow transition duration-300 text-sm">
                    العودة
                </button>
            </div>
        </div>
    </div>

    <!-- مودال الإضافة -->
    <div x-show="openAddModal" x-cloak x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">

        <div @click.outside="closeAddModal()"
            class="relative bg-white rounded-xl shadow-xl w-full max-w-md p-6 border border-gray-200 transform transition-all duration-300"
            :class="openAddModal ? 'opacity-100 scale-100' : 'opacity-0 scale-95'">

            <h2 class="text-xl font-bold text-[rgb(var(--primary-700))] mb-4">إضافة تسلسل جديد</h2>

            <!-- اسم التسلسل -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-600 mb-1">اسم التسلسل:</label>
                <input type="text" wire:model="name" class="{{ $fieldClass }}" />
                @error('name')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- نوع الإجراء -->
            <select wire:model="action_type" class="{{ $fieldClass }}">
    <option value="">-- اختر نوع الإجراء --</option>
    @foreach($actionTypes as $key => $label)
        <option value="{{ $key }}">{{ $label }}</option>
    @endforeach
</select>


            <!-- الموظفين المسؤولين -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-600 mb-2">الموظفون المسؤولون عن الموافقة:</label>

                @foreach ($approvers as $index => $userId)
                    <div class="flex items-center gap-2 mb-3">
                        <div class="{{ $containerClass }} flex-grow">
                            @php
                                $usersOptions = \App\Models\User::where('agency_id', auth()->user()->agency_id)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            @endphp

                            <x-select-field wireModel="approvers.{{ $index }}"
                                name="approvers_{{ $index }}" label="اختر موظف" :options="$usersOptions" />
                            @error('approvers.' . $index)
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        @if ($loop->last)
                            <button type="button" wire:click="addApproverField"
                                class="flex-shrink-0 w-9 h-9 flex items-center justify-center rounded-full bg-[rgb(var(--primary-500))] hover:bg-[rgb(var(--primary-600))] text-white shadow transition"
                                title="إضافة موظف">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                        @endif

                        @if (count($approvers) > 1)
                            @can('sequences.delete')
                                <button type="button" wire:click="removeApproverField({{ $index }})"
                                    class="flex-shrink-0 w-9 h-9 flex items-center justify-center rounded-full bg-red-100 hover:bg-red-200 text-red-600 shadow transition"
                                    title="حذف الموظف">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            @endcan
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- الأزرار -->
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 mt-4">
                <button type="button" wire:click="closeAddModal"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm">
                    إلغاء
                </button>

                <button wire:click="saveSequence" wire:loading.attr="disabled"
                    class="px-6 py-2 text-white rounded-xl font-semibold shadow-md hover:shadow-xl transition duration-300 text-sm"
                    style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                    حفظ
                </button>
            </div>
        </div>
    </div>

    <!-- مودال التعديل -->
    <div x-show="openEditModal" x-cloak x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">

        <div @click.outside="closeEditModal()"
            class="relative bg-white rounded-xl shadow-xl w-full max-w-md p-6 border border-gray-200 transform transition-all duration-300"
            :class="openEditModal ? 'opacity-100 scale-100' : 'opacity-0 scale-95'">

            <h2 class="text-xl font-bold text-[rgb(var(--primary-700))] mb-4">تعديل التسلسل</h2>

            <!-- اسم التسلسل -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-600 mb-1">اسم التسلسل:</label>
                <input type="text" wire:model="editName" class="{{ $fieldClass }}" />
                @error('editName')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- نوع الإجراء -->
            <select wire:model="editActionType" class="{{ $fieldClass }}">
    <option value="">-- اختر نوع الإجراء --</option>
    @foreach($actionTypes as $key => $label)
        <option value="{{ $key }}">{{ $label }}</option>
    @endforeach
</select>


            <!-- الموظفين المسؤولين -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-600 mb-2">الموظفون المسؤولون عن الموافقة:</label>

                @foreach ($editApprovers as $index => $userId)
                    <div class="flex items-center gap-2 mb-3">
                        <div class="{{ $containerClass }} flex-grow">
                            <x-select-field wireModel="editApprovers.{{ $index }}"
                                name="editApprovers_{{ $index }}" label="اختر موظف" :options="$usersOptions" />
                            @error('editApprovers.' . $index)
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        @if ($loop->last)
                            <button type="button" wire:click="addEditApproverField"
                                class="flex-shrink-0 w-9 h-9 flex items-center justify-center rounded-full bg-[rgb(var(--primary-500))] hover:bg-[rgb(var(--primary-600))] text-white shadow transition"
                                title="إضافة موظف">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                        @endif

                        @if (count($editApprovers) > 1)
                            @can('sequences.delete')
                                <button type="button" wire:click="removeEditApproverField({{ $index }})"
                                    class="flex-shrink-0 w-9 h-9 flex items-center justify-center rounded-full bg-red-100 hover:bg-red-200 text-red-600 shadow transition"
                                    title="حذف الموظف">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            @endcan
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- الأزرار -->
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 mt-4">
                <button type="button" @click="closeEditModal()"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm">
                    إلغاء
                </button>

                <button wire:click="updateSequence" wire:loading.attr="disabled"
                    class="px-6 py-2 text-white rounded-xl font-semibold shadow-md hover:shadow-xl transition duration-300 text-sm"
                    style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                    حفظ
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function sequenceManager() {
        return {
            openAddModal: false,
            openEditModal: false,
            openViewModal: false,

            init() {
                Livewire.on('openAddModal', () => {
                    this.openAddModal = true;
                });
                Livewire.on('closeAddModal', () => {
                    this.openAddModal = false;
                });
                Livewire.on('openEditModal', () => {
                    this.openEditModal = true;
                });
                Livewire.on('closeEditModal', () => {
                    this.openEditModal = false;
                });
                Livewire.on('viewModalOpened', () => {
                    this.openViewModal = true;
                });
                Livewire.on('closeViewModal', () => {
                    this.openViewModal = false;
                });

                // مراقبة تغييرات المودالات في Alpine للتزامن مع Livewire
                this.$watch('openAddModal', (val) => {
                    if (!val) {
                        $wire.closeAddModal();
                    }
                });
                this.$watch('openEditModal', (val) => {
                    if (!val) {
                        $wire.closeEditModal();
                    }
                });
                this.$watch('openViewModal', (val) => {
                    $wire.showViewModal = val;
                });
            },

            closeAddModal() {
                this.openAddModal = false;
            },
            closeEditModal() {
                this.openEditModal = false;
            },
            closeViewModal() {
                this.openViewModal = false;
            },
        }
    }
</script>
