<div x-data="sequenceManager()" x-init="init()" class="p-6 bg-gray-50 min-h-screen">

    @can('sequences.create')
    <div class="mb-6">
        <button
            class="text-white font-semibold rounded-lg px-5 py-2 shadow transition"
            style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);"
            @click="$wire.openAddModal()">
            + إضافة تسلسل جديد
        </button>
    </div>
    @endcan

    <!-- جدول التسلسلات -->
    @php
        use App\Tables\ApprovalSequenceTable;
        $columns = ApprovalSequenceTable::columns();
    @endphp
    <x-data-table :rows="$sequences" :columns="$columns" />

    <!-- مودال العرض -->
    <div x-show="openViewModal" x-cloak x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-white bg-opacity-100 p-6">

        <div @click.outside="openViewModal = false"
            class="relative bg-white rounded-3xl shadow-xl p-8 max-w-lg w-full text-center font-sans border border-gray-200">

            <h2 class="text-xl font-bold text-gray-900 mb-6 border-b pb-4">عرض تسلسل الموافقات</h2>

            <div class="mb-6 text-base text-gray-800 space-y-3">
                <p><strong>اسم التسلسل:</strong> <span class="font-semibold text-emerald-600">{{ $viewName }}</span>
                </p>
                <p><strong>نوع الإجراء:</strong> <span
                        class="font-semibold text-emerald-600">{{ $viewActionType }}</span></p>
            </div>

            <!-- عرض الموظفين في صف أفقي مع أسهم -->
            <div
                class="flex items-center justify-center gap-2 flex-nowrap overflow-x-auto whitespace-nowrap text-sm font-semibold text-emerald-800">
                @foreach ($viewApprovers as $index => $approver)
                    <div class="flex items-center space-x-2 rtl:space-x-reverse select-none">
                        <span
                            class="bg-[rgba(var(--primary-100),0.5)] px-4 py-2 rounded-lg shadow whitespace-nowrap text-[rgb(var(--primary-700))]">
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

            <!-- زر العودة -->
            <button @click="openViewModal = false"
                class="mt-8 inline-block px-8 py-3 rounded-xl bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold transition-shadow shadow-md focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:ring-offset-2">
                العودة للصفحة
            </button>
        </div>
    </div>



    <!-- مودال الإضافة -->
    <div x-show="openAddModal" x-cloak x-transition
        class="fixed inset-0 z-50 flex items-start justify-center bg-gray-100 bg-opacity-50 backdrop-blur-sm p-4"
        style="padding-top: 6rem;">
        <div class="absolute inset-0 pointer-events-none">
            <div
                class="absolute top-0 left-1/4 w-64 h-64 bg-orange-300 rounded-full filter blur-3xl opacity-40 animate-pulse">
            </div>
            <div
                class="absolute bottom-0 right-1/4 w-64 h-64 bg-blue-300 rounded-full filter blur-3xl opacity-40 animate-pulse">
            </div>
        </div>

        <div @click.outside="closeAddModal()"
            class="relative bg-white w-full max-w-md p-6 rounded-2xl shadow-xl space-y-4">
            <h2 class="text-xl font-extrabold text-gray-900">إضافة تسلسل جديد</h2>

            <!-- اسم التسلسل -->
            <input type="text" wire:model="name" placeholder="اسم التسلسل"
                class="w-full border border-gray-300 rounded-md px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" />
            @error('name')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror

            <!-- نوع الإجراء -->
            <select wire:model="action_type"
                class="w-full border border-gray-300 rounded-md px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 my-4">
                <option value="">-- اختر نوع الإجراء --</option>
                @foreach ($actionTypes as $type)
                    <option value="{{ $type }}">{{ $type }}</option>
                @endforeach
            </select>
            @error('action_type')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror

            <!-- الموظفين المسؤولين -->
            <label class="block font-semibold text-gray-800 mb-2">الموظفون المسؤولون عن الموافقة:</label>
            @foreach ($approvers as $index => $userId)
                <div class="flex items-center gap-3 mb-3">
                    <select wire:model="approvers.{{ $index }}"
                        class="flex-grow border border-gray-300 rounded-md px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="">-- اختر موظف --</option>
                        @foreach (\App\Models\User::where('agency_id', auth()->user()->agency_id)->get() as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>

                    @if ($loop->last)
                        <button type="button" wire:click="addApproverField"
                            class="flex-shrink-0 bg-emerald-600 hover:bg-emerald-700 text-white rounded px-4 py-2 font-semibold transition">+</button>
                    @endif

                    @if (count($approvers) > 1)
                        @can('sequences.delete')
                        <button type="button" wire:click="removeApproverField({{ $index }})"
                            class="flex-shrink-0 text-red-600 hover:text-red-800 font-semibold">حذف</button>
                        @endcan
                    @endif
                </div>
                @error('approvers.' . $index)
                    <p class="text-red-600 text-sm mb-2">{{ $message }}</p>
                @enderror
            @endforeach

            <!-- الأزرار -->
            <div class="flex justify-end gap-4 pt-4 border-t border-gray-200">
                <button type="button" @click="closeAddModal()"
                    class="px-6 py-3 bg-gray-100 hover:bg-gray-200 rounded-md font-semibold text-gray-700 transition">
                    إلغاء
                </button>

                <button wire:click="saveSequence" wire:loading.attr="disabled"
                    class="px-6 py-3 text-white rounded-md font-semibold transition"
                    style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                    حفظ
                </button>

            </div>
        </div>
    </div>

    <!-- مودال التعديل -->
    <div x-show="openEditModal" x-cloak x-transition
        class="fixed inset-0 z-50 flex items-start justify-center bg-gray-100 bg-opacity-50 backdrop-blur-sm p-4"
        style="padding-top: 6rem;">
        <div class="absolute inset-0 pointer-events-none">
            <div
                class="absolute top-0 left-1/4 w-64 h-64 bg-orange-300 rounded-full filter blur-3xl opacity-40 animate-pulse">
            </div>
            <div
                class="absolute bottom-0 right-1/4 w-64 h-64 bg-blue-300 rounded-full filter blur-3xl opacity-40 animate-pulse">
            </div>
        </div>

        <div @click.outside="closeEditModal()"
            class="relative bg-white w-full max-w-md p-6 rounded-2xl shadow-xl space-y-4">
            <h2 class="text-xl font-extrabold text-gray-900">تعديل التسلسل</h2>

            <!-- اسم التسلسل -->
            <input type="text" wire:model="editName" placeholder="اسم التسلسل"
                class="w-full border border-gray-300 rounded-md px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" />
            @error('editName')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror

            <!-- نوع الإجراء -->
            <select wire:model="editActionType"
                class="w-full border border-gray-300 rounded-md px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 my-4">
                <option value="">-- اختر نوع الإجراء --</option>
                @foreach ($actionTypes as $type)
                    <option value="{{ $type }}">{{ $type }}</option>
                @endforeach
            </select>
            @error('editActionType')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror

            <!-- الموظفين المسؤولين -->
            <label class="block font-semibold text-gray-800 mb-2">الموظفون المسؤولون عن الموافقة:</label>
            @foreach ($editApprovers as $index => $userId)
                <div class="flex items-center gap-3 mb-3">
                    <select wire:model="editApprovers.{{ $index }}"
                        class="flex-grow border border-gray-300 rounded-md px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="">-- اختر موظف --</option>
                        @foreach (\App\Models\User::where('agency_id', auth()->user()->agency_id)->get() as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>

                    @if ($loop->last)
                        <button type="button" wire:click="addEditApproverField"
                            class="flex-shrink-0 bg-emerald-600 hover:bg-emerald-700 text-white rounded px-4 py-2 font-semibold transition">+</button>
                    @endif

                    @if (count($editApprovers) > 1)
                        <button type="button" wire:click="removeEditApproverField({{ $index }})"
                            class="flex-shrink-0 text-red-600 hover:text-red-800 font-semibold">حذف</button>
                    @endif
                </div>
                @error('editApprovers.' . $index)
                    <p class="text-red-600 text-sm mb-2">{{ $message }}</p>
                @enderror
            @endforeach

            <!-- الأزرار -->
            <div class="flex justify-end gap-4 pt-4 border-t border-gray-200">
                <button type="button" @click="closeEditModal()"
                    class="px-6 py-3 bg-gray-100 hover:bg-gray-200 rounded-md font-semibold text-gray-700 transition">
                    إلغاء
                </button>

                <button wire:click="updateSequence" wire:loading.attr="disabled"
                    class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md font-semibold transition">
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
