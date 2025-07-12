<div class="space-y-6">

    <!-- الكارد الموحد (بلون الثيم) -->
    <div class="rounded-xl p-6 mb-6 shadow border border-gray-100"
         style="background: linear-gradient(135deg, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
        <div class="flex flex-col md:flex-row-reverse justify-between items-center gap-6">

            <!-- شعار الوكالة -->
            <div class="h-28 w-28 rounded-full overflow-hidden shadow border border-gray-200 bg-white">
                @php $logoPath = Auth::user()->agency->logo; @endphp
                @if($logoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoPath))
                    <img src="{{ asset('storage/' . $logoPath) }}" alt="شعار الوكالة"
                         class="h-full w-full object-cover">
                @else
                    <div class="h-full w-full flex items-center justify-center bg-gray-100 text-gray-400 text-4xl font-bold">
                        {{ strtoupper(substr(Auth::user()->agency->name, 0, 1)) }}
                    </div>
                @endif
            </div>

            <!-- محتوى الترحيب والمستخدم -->
            <div class="flex-1 text-center md:text-right text-white">
                <h2 class="text-xl md:text-2xl font-bold mb-1">
                    مرحباً {{ auth()->user()->name }} في {{ $this->agencyInfo->name }}
                </h2>
                <p class="text-sm text-white/80">{{ auth()->user()->email }}</p>
                <p class="text-sm text-white/80">
                    <i class="fas fa-user-tag mr-1"></i>
                    @if(auth()->user()->roles->first())
                        {{ auth()->user()->roles->first()->name }}
                    @else
                        مستخدم عادي
                    @endif
                </p>
            </div>

        </div>
    </div>

</div>
