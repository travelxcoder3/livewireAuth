<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ChangePassword extends Component
{
    public $password, $password_confirmation;

    public function updatePassword()
    {
        $this->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();
        $user->password = Hash::make($this->password);
        $user->must_change_password = false;
        $user->save();

        session()->flash('success', 'تم تغيير كلمة المرور بنجاح.');

        return redirect()->route('admin.dashboard');
    }

    public function render()
    {
        return view('livewire.admin.change-password')->layout('layouts.admin');
    }
} 