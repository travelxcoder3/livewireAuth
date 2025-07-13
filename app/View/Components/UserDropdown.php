<?php
namespace App\View\Components;

use Illuminate\View\Component;

class UserDropdown extends Component
{
    public $name, $role, $logoutRoute;

    public function __construct($name = null, $role = null, $logoutRoute = null)
    {
        $this->name = $name ?? (auth()->user()->name ?? 'User Name');
        $this->role = $role ?? (auth()->user()?->getRoleNames()->first() ?? 'الدور غير محدد');
        $this->logoutRoute = $logoutRoute ?? route('logout');
    }

    public function render()
    {
        return view('components.navbar.user.user-dropdown');
    }
} 