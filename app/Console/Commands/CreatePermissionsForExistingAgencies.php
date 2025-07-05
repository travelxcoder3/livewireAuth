<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Agency;
use Database\Seeders\PermissionSeeder;

class CreatePermissionsForExistingAgencies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agencies:create-permissions-for-existing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'إنشاء الصلاحيات الأساسية للوكالات الموجودة';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $agencies = Agency::all();
        
        if ($agencies->isEmpty()) {
            $this->warn("لا توجد وكالات في النظام!");
            return 0;
        }

        $this->info("جاري إنشاء الصلاحيات لـ {$agencies->count()} وكالة...");
        
        $permissionSeeder = new PermissionSeeder();
        $bar = $this->output->createProgressBar($agencies->count());
        $bar->start();

        foreach ($agencies as $agency) {
            // إنشاء الصلاحيات الأساسية
            $permissionSeeder->createPermissionsForAgency($agency->id);
            
            // إنشاء دور agency-admin
            $agency->createAgencyAdminRole();
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("تم إنشاء الصلاحيات والأدوار لجميع الوكالات بنجاح!");
        
        return 0;
    }
} 