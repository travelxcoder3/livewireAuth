<div class="p-8 max-w-md mx-auto">
    <div class="bg-white rounded-2xl shadow-2xl p-8 border-t-4 border-red-500">
        <h3 class="text-2xl font-bold mb-6 text-red-600 text-center">تأكيد حذف الوكالة</h3>
        <p class="text-center text-gray-700 mb-8">هل أنت متأكد أنك تريد حذف الوكالة <span class="font-bold text-red-700">{{ $agencyName }}</span>؟ لا يمكن التراجع عن هذا الإجراء.</p>
        <div class="flex justify-center gap-4">
            <button wire:click="confirmDelete" class="bg-red-600 hover:bg-red-700 text-white px-8 py-3 rounded-lg font-bold text-lg transition duration-200 shadow-lg hover:shadow-xl">تأكيد الحذف</button>
            <button wire:click="cancelDelete" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-8 py-3 rounded-lg font-bold text-lg transition duration-200">إلغاء</button>
        </div>
    </div>
</div> 