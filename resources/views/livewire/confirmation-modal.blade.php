<div>
    @if($show)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-900">{{ $title }}</h3>
                    <button @click="cancel" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <div class="mb-6 text-gray-700">
                    {{ $message }}
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button @click="cancel" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition">
                        {{ $cancelText }}
                    </button>
                    <button @click="confirm" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition">
                        {{ $confirmText }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>