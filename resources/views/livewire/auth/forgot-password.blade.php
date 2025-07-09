@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-emerald-50 via-teal-50 to-cyan-50 p-4">
    <div class="w-full max-w-md">
        <div class="bg-white/90 rounded-3xl shadow-2xl p-8 space-y-8 border border-emerald-100 backdrop-blur-md">
            <div class="text-center">
                <h2 class="text-2xl font-extrabold text-emerald-700 mb-1">استعادة كلمة المرور</h2>
                <p class="text-emerald-400 font-medium">أدخل بريدك الإلكتروني لإرسال رابط إعادة تعيين كلمة المرور</p>
            </div>
            @if(session('message'))
                <div class="bg-emerald-100 text-emerald-700 rounded-lg p-3 text-center mb-4">{{ session('message') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 text-red-700 rounded-lg p-3 text-center mb-4">{{ session('error') }}</div>
            @endif
            <form wire:submit.prevent="sendResetLink" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-bold text-emerald-600 mb-2">البريد الإلكتروني</label>
                    <input
                        wire:model="email"
                        type="email"
                        id="email"
                        class="block w-full pr-3 py-3 border border-teal-200 rounded-xl focus:ring-2 focus:ring-emerald-300 focus:border-emerald-400 transition duration-200 bg-white/80 placeholder-teal-300 text-emerald-700 font-semibold shadow-sm @error('email') border-red-400 focus:ring-red-200 focus:border-red-400 @enderror"
                        placeholder="example@email.com"
                        dir="ltr"
                    >
                    @error('email')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent text-base font-bold rounded-xl text-white bg-gradient-to-r from-emerald-400 via-teal-400 to-cyan-400 shadow-lg hover:from-emerald-500 hover:to-cyan-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-200 transition duration-200 transform hover:scale-105">
                        إرسال رابط إعادة التعيين
                    </button>
                </div>
            </form>
            <div class="text-center mt-4">
                <a href="/login" class="text-emerald-400 hover:text-teal-500 font-bold">العودة لتسجيل الدخول</a>
            </div>
        </div>
    </div>
</div>
@endsection
