<div>
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">تغيير كلمة المرور</h1>
        <p class="text-gray-600">يجب عليك تغيير كلمة المرور قبل المتابعة</p>
    </div>

    <div class="max-w-md mx-auto">
        <div class="bg-white rounded-xl shadow-lg p-6">
            @if (session()->has('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <form wire:submit.prevent="updatePassword">
                <!-- كلمة المرور الجديدة -->
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        كلمة المرور الجديدة
                    </label>
                    <input type="password" 
                           id="password" 
                           wire:model="password"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                           placeholder="أدخل كلمة المرور الجديدة">
                    @error('password')
                        <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- تأكيد كلمة المرور الجديدة -->
                <div class="mb-6">
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                        تأكيد كلمة المرور الجديدة
                    </label>
                    <input type="password" 
                           id="password_confirmation" 
                           wire:model="password_confirmation"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                           placeholder="أعد إدخال كلمة المرور الجديدة">
                    @error('password_confirmation')
                        <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- زر التحديث -->
                <button type="submit" 
                        class="w-full bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-semibold py-2 px-4 rounded-lg hover:from-emerald-600 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition duration-200">
                    تحديث كلمة المرور
                </button>
            </form>
        </div>
    </div>
</div> 