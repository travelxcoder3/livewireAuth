<?php

namespace App\Livewire\Agency\Backups;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Index extends Component
{
    public int $agencyId;
    public $backups = [];

    private function assertAgencyAccess(int $agencyId): void
    {
        if (!Auth::user()->hasRole('super-admin') && (int)Auth::user()->agency_id !== $agencyId) {
            abort(403);
        }
    }

    public function mount($agency): void
    {
        $agency = (int) $agency;
        $this->assertAgencyAccess($agency);

        $this->agencyId = $agency;
        $this->loadBackups();
    }

    public function loadBackups(): void
    {
        $this->backups = DB::table('agency_backups')
            ->where('agency_id', $this->agencyId)
            ->orderByDesc('id')
            ->get();
    }

    public function render()
    {
        return view('livewire.agency.backups.index')
            ->layout('layouts.agency', [
                'title' => "النسخ الاحتياطية للوكالة #{$this->agencyId}",
            ]);
    }
}
