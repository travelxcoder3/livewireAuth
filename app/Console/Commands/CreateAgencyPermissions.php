<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Agency;
use Database\Seeders\PermissionSeeder;

class CreateAgencyPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agencies:create-permissions {--agency-id= : إنشاء صلاحيات لوكالة محددة}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'إنشاء الصلاحيات الأساسية لجميع الوكالات أو وكالة محددة';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $agencyId = $this->option('agency-id');
        $permissionSeeder = new PermissionSeeder();

        if ($agencyId) {
            // إنشاء صلاحيات لوكالة محددة
            $agency = Agency::find($agencyId);
            if (!$agency) {
                $this->error("الوكالة برقم {$agencyId} غير موجودة!");
                return 1;
            }

            $this->info("جاري إنشاء الصلاحيات لوكالة: {$agency->name}");
            $permissionSeeder->createPermissionsForAgency($agency->id);
            $agency->createAgencyAdminRole();
            $this->info("تم إنشاء الصلاحيات والأدوار بنجاح!");
        } else {
            // إنشاء صلاحيات لجميع الوكالات
            $agencies = Agency::all();
            
            if ($agencies->isEmpty()) {
                $this->warn("لا توجد وكالات في النظام!");
                return 0;
            }

            $this->info("جاري إنشاء الصلاحيات لـ {$agencies->count()} وكالة...");
            
            $bar = $this->output->createProgressBar($agencies->count());
            $bar->start();

            foreach ($agencies as $agency) {
                $permissionSeeder->createPermissionsForAgency($agency->id);
                $agency->createAgencyAdminRole();
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info("تم إنشاء الصلاحيات والأدوار لجميع الوكالات بنجاح!");
        }

        return 0;
    }
} 