<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Login extends Component
{
    public $email = '';
    public $password = '';
    public $showPassword = false;
    public $remember = false;

    public function togglePassword()
    {
        $this->showPassword = !$this->showPassword;
    }

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

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            session()->regenerate();
            $user = Auth::user();
            // منع الدخول إذا انتهت فترة اشتراك الوكالة
            if ($user->agency && $user->agency->subscription_end_date && now()->greaterThan($user->agency->subscription_end_date)) {
                Auth::logout();
                session()->flash('error', 'انتهت فترة اشتراك وكالتك ولا يمكنك الدخول. يرجى التواصل مع الإدارة لتجديد الاشتراك.');
                return;
            }
            // منع الدخول إذا كانت حالة الوكالة غير نشطة أو موقوفة
            if ($user->agency && $user->agency->status !== 'active') {
                Auth::logout();
                session()->flash('error', 'وكالتك غير نشطة، يرجى التواصل مع الإدارة.');
                return;
            }
            // منع الدخول إذا كان حساب المستخدم نفسه غير نشط أو موقوف
            if (isset($user->is_active) && !$user->is_active) {
                Auth::logout();
                session()->flash('error', 'حسابك غير نشط أو موقوف، يرجى التواصل مع الإدارة.');
                return;
            }
            \Log::info('User roles after login: ' . json_encode($user->getRoleNames()));
            if ($user->hasRole('super-admin')) {
                return redirect()->to('/admin/dashboard');
            } elseif ($user->hasRole('agency-admin')) {
                return redirect()->to('/agency/dashboard');
            } elseif ($user->agency_id) {
                if ($user->can('users.view')) {
                    return redirect()->to('/agency/users');
                } elseif ($user->can('roles.view')) {
                    return redirect()->to('/agency/dashboard');
                } elseif ($user->can('permissions.view')) {
                    return redirect()->to('/agency/permissions');
                } else {
                    return redirect()->to('/agency/dashboard');
                }
            } else {
                return redirect()->to('/');
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
