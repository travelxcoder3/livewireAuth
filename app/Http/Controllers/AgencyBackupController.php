<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage, Auth};
use App\Services\AgencyBackupService;

class AgencyBackupController extends Controller
{
    private function assertAgencyAccess($agencyId): void
    {
        // تأكد أن مدير الوكالة لا يصل إلا لوكالته
        if (!Auth::user()->hasRole('super-admin') && (int)Auth::user()->agency_id !== (int)$agencyId) {
            abort(403);
        }
    }

    public function index($agency)
    {
        $this->assertAgencyAccess($agency);

        $rows = DB::table('agency_backups')
            ->where('agency_id', $agency)
            ->orderByDesc('id')
            ->get();

        return view('livewire.agency.backups.index', [
            'agencyId' => (int) $agency,
            'backups'  => $rows,
        ]);
    }

    public function store($agency, AgencyBackupService $svc)
    {
        $this->assertAgencyAccess($agency);
        $file = $svc->create((int) $agency);
        return back()->with('ok', "تم إنشاء النسخة: {$file}");
    }

    public function download($agency, $file)
    {
        $this->assertAgencyAccess($agency);

        $path = Storage::disk('agency_backups')->path($file);
        abort_unless(file_exists($path), 404);

        return response()->download($path);
    }

    // استعادة من ملف جديد مرفوع
    public function restore($agency, Request $request, AgencyBackupService $svc)
    {
        $this->assertAgencyAccess($agency);

        $request->validate([
            'zip' => ['required', 'file', 'mimes:zip'],
        ]);

        $uploaded = $request->file('zip');

        // اسم آمن للملف وحفظه على ديسك النسخ الاحتياطية مباشرةً
        $orig = preg_replace('/[^A-Za-z0-9_.-]/', '_', $uploaded->getClientOriginalName());
        $name = 'upload_' . time() . '_' . $orig;

        Storage::disk('agency_backups')->put($name, file_get_contents($uploaded->getRealPath()));

        // تنفيذ الاستعادة من الملف المحفوظ
        $svc->restore((int) $agency, $name);

        return back()->with('ok', 'تمت الاستعادة بنجاح من الملف المرفوع.');
    }

    // استعادة من ملف موجود في القائمة
    public function restoreExisting($agency, $file, AgencyBackupService $svc)
    {
        $this->assertAgencyAccess($agency);
        $svc->restore((int) $agency, $file);
        return back()->with('ok', "تمت الاستعادة من: {$file}");
    }
}
