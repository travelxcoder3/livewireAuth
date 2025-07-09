<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Password;

class ForgotPassword extends Component
{
    public $email = '';
    public $success = false;

    protected $rules = [
        'email' => 'required|email|exists:users,email',
    ];

    public function sendResetLink()
    {
        $this->validate();
        $status = Password::sendResetLink(['email' => $this->email]);
        if ($status === Password::RESET_LINK_SENT) {
            $this->success = true;
            session()->flash('message', 'تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني.');
        } else {
            session()->flash('error', 'حدث خطأ أثناء الإرسال. حاول مرة أخرى.');
        }
    }

    public function render()
    {
        return view('livewire.forgot-password')
            ->layout('layouts.app')
            ->title('استعادة كلمة المرور');
    }
}
