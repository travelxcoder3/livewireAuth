<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Login extends Component
{
    public $email = '';
    public $password = '';

    public function login()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة',
            'password.required' => 'كلمة المرور مطلوبة',
        ]);

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            session()->regenerate();
            $user = Auth::user();
            \Log::info('User roles after login: ' . json_encode($user->getRoleNames()));
            if ($user->hasRole('super-admin')) {
                return redirect()->intended('/admin/dashboard');
            } elseif ($user->hasRole('agency-admin')) {
                return redirect()->intended('/agency/dashboard');
            } elseif ($user->agency_id) {
                if ($user->can('users.view')) {
                    return redirect()->intended('/agency/users');
                } elseif ($user->can('roles.view')) {
                    return redirect()->intended('/agency/dashboard');
                } elseif ($user->can('permissions.view')) {
                    return redirect()->intended('/agency/permissions');
                } else {
                    return redirect()->intended('/agency/dashboard');
                }
            } else {
                return redirect('/');
            }
        } else {
            session()->flash('error', 'بيانات الدخول غير صحيحة');
        }
    }

    public function render()
    {
        return view('livewire.auth.login')
            ->layout('layouts.app')
            ->title('تسجيل الدخول - نظام إدارة وكالات السفر');
    }
}
