@php
use App\Tables\ApprovalSequenceTable;
$columns = ApprovalSequenceTable::columns();

$fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer';
$labelClass = 'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]';
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
    <x-data-table :rows="$sequences" :columns="$columns" />

    <!-- مودال العرض -->
    <div x-show="openViewModal" x-cloak x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">

        <div @click.outside="openViewModal = false"
            class="relative bg-white rounded-xl shadow-xl p-6 max-w-lg w-full border border-gray-200 transform transition-all duration-300"
            :class="openViewModal ? 'opacity-100 scale-100' : 'opacity-0 scale-95'">

            <h2 class="text-xl font-bold text-[rgb(var(--primary-700))] mb-4 border-b pb-3">عرض تسلسل الموافقات</h2>

            <div class="mb-6 text-sm text-gray-700 space-y-3">
                <p class="flex justify-between">
                    <span class="font-semibold text-gray-600">اسم التسلسل:</span>
                    <span class="font-semibold text-[rgb(var(--primary-600))]">{{ $viewName }}</span>
                </p>
                <p class="flex justify-between">
                    <span class="font-semibold text-gray-600">نوع الإجراء:</span>
                    <span class="font-semibold text-[rgb(var(--primary-600))]">{{ $viewActionType }}</span>
                </p>
            </div>

            <!-- عرض الموظفين في صف أفقي مع أسهم -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm font-semibold text-gray-600 mb-3">مسار الموافقة:</p>
                <div class="flex items-center justify-start gap-2 flex-nowrap overflow-x-auto pb-2">
                    @foreach ($viewApprovers as $index => $approver)
                        <div class="flex items-center space-x-2 rtl:space-x-reverse select-none shrink-0">
                            <span class="bg-[rgba(var(--primary-100),0.5)] px-4 py-2 rounded-lg text-sm whitespace-nowrap text-[rgb(var(--primary-700))]">
                                {{ $approver }}
                            </span>
                            @if (!$loop->last)
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 flex-shrink-0" 
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
            <div class="{{ $containerClass }} mb-4">
                <input type="text" wire:model="name" placeholder="اسم التسلسل" class="{{ $fieldClass }}" />
                <label class="{{ $labelClass }}">اسم التسلسل</label>
                @error('name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- نوع الإجراء -->
            <div class="{{ $containerClass }} mb-4">
                <input type="text" wire:model="action_type" class="{{ $fieldClass }}" placeholder="اكتب نوع الإجراء" />
                <label class="{{ $labelClass }}">نوع الإجراء</label>
                @error('action_type') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- الموظفين المسؤولين -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-600 mb-2">الموظفون المسؤولون عن الموافقة:</label>
                
                @foreach ($approvers as $index => $userId)
                    <div class="flex items-center gap-2 mb-3">
                        <div class="{{ $containerClass }} flex-grow">
                        @php
                                $usersOptions = \App\Models\User::where('agency_id', auth()->user()->agency_id)->pluck('name', 'id')->toArray();
                            @endphp

                            <x-select-field 
                                wireModel="approvers.{{ $index }}"
                                name="approvers_{{ $index }}"
                                label="اختر موظف"
                                :options="$usersOptions"
                            />
                            @error('approvers.' . $index) <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        @if ($loop->last)
                            <button type="button" wire:click="addApproverField"
                                class="flex-shrink-0 text-white bg-[rgb(var(--primary-500))] hover:bg-[rgb(var(--primary-600))] rounded-lg px-3 py-2 shadow transition text-sm">
                                +
                            </button>
                        @endif

                        @if (count($approvers) > 1)
                            @can('sequences.delete')
                            <button type="button" wire:click="removeApproverField({{ $index }})"
                                class="flex-shrink-0 text-red-600 hover:text-red-800 text-sm font-semibold">
                                حذف
                            </button>
                            @endcan
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- الأزرار -->
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 mt-4">
            <button type="button" wire:click="closeForm"
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
            <div class="{{ $containerClass }} mb-4">
                <input type="text" wire:model="editName" placeholder="اسم التسلسل" class="{{ $fieldClass }}" />
                <label class="{{ $labelClass }}">اسم التسلسل</label>
                @error('editName') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- نوع الإجراء (تعديل) -->
            <div class="{{ $containerClass }} mb-4">
                <input type="text" wire:model="editActionType" class="{{ $fieldClass }}" placeholder="اكتب نوع الإجراء" />
                <label class="{{ $labelClass }}">نوع الإجراء</label>
                @error('editActionType') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- الموظفين المسؤولين -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-600 mb-2">الموظفون المسؤولون عن الموافقة:</label>
                
                @foreach ($editApprovers as $index => $userId)
                    <div class="flex items-center gap-2 mb-3">
                        <div class="{{ $containerClass }} flex-grow">
                        <x-select-field 
                                wireModel="editApprovers.{{ $index }}"
                                name="editApprovers_{{ $index }}"
                                label="اختر موظف"
                                :options="$usersOptions"
                            />
                            @error('editApprovers.' . $index) <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        @if ($loop->last)
                            <button type="button" wire:click="addEditApproverField"
                                class="flex-shrink-0 text-white bg-[rgb(var(--primary-500))] hover:bg-[rgb(var(--primary-600))] rounded-lg px-3 py-2 shadow transition text-sm">
                                +
                            </button>
                        @endif

                        @if (count($editApprovers) > 1)
                            <button type="button" wire:click="removeEditApproverField({{ $index }})"
                                class="flex-shrink-0 text-red-600 hover:text-red-800 text-sm font-semibold">
                                حذف
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- الأزرار -->
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 mt-4">
                <button type="button" @click="closeEditModal()"
                    class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-xl font-semibold shadow transition duration-300 text-sm">
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