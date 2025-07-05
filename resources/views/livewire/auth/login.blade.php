<div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f8fafc;">
    <form wire:submit.prevent="login" style="background: #fff; padding: 2.5rem 2rem; border-radius: 12px; box-shadow: 0 2px 16px #0001; min-width: 340px; max-width: 95vw;">
        <h2 style="text-align: center; color: #2563eb; font-weight: bold; font-size: 1.6rem; margin-bottom: 1.5rem;">تسجيل الدخول</h2>
        @if(session('error'))
            <div style="background: #fee2e2; color: #b91c1c; padding: 0.7rem 1rem; border-radius: 6px; margin-bottom: 1rem; text-align: center;">
                {{ session('error') }}
            </div>
        @endif
        <div style="margin-bottom: 1.2rem;">
            <label for="email" style="display: block; margin-bottom: 0.4rem; color: #374151;">البريد الإلكتروني</label>
            <input type="email" id="email" wire:model.defer="email" style="width: 100%; padding: 0.7rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem;">
            @error('email')
                <span style="color: #b91c1c; font-size: 0.95rem;">{{ $message }}</span>
            @enderror
        </div>
        <div style="margin-bottom: 1.2rem;">
            <label for="password" style="display: block; margin-bottom: 0.4rem; color: #374151;">كلمة المرور</label>
            <input type="password" id="password" wire:model.defer="password" style="width: 100%; padding: 0.7rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem;">
            @error('password')
                <span style="color: #b91c1c; font-size: 0.95rem;">{{ $message }}</span>
            @enderror
        </div>
        <button type="submit" style="width: 100%; background: #2563eb; color: #fff; font-weight: bold; padding: 0.8rem; border: none; border-radius: 6px; font-size: 1.1rem; margin-top: 0.5rem; cursor: pointer; transition: background 0.2s;">دخول</button>
    </form>
</div>
