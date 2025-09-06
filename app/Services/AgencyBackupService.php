<?php

namespace App\Services;

use Illuminate\Support\Facades\{DB, Storage, Auth};
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use ZipArchive;

class AgencyBackupService
{
    /** إنشاء نسخة ZIP مفلترة بالـ agency_id + تضم جداول مشتركة كاملة (INSERT فقط بلا DDL) */
    public function create(int $agencyId): string
    {
        $disk = Storage::disk('agency_backups');
        if (!is_dir($disk->path(''))) mkdir($disk->path(''), 0775, true);

        $ts       = now()->format('Ymd_His');
        $basename = "agency_{$agencyId}_{$ts}"; // التزم بصيغة الواجهة

        $workDir  = $disk->path("__tmp_{$basename}");
        $sqlPath  = "{$workDir}/payload.sql";
        $zipPath  = $disk->path("{$basename}.zip");

        if (!is_dir($workDir)) mkdir($workDir, 0775, true);

        $dbName = DB::getDatabaseName();

        // جداول بها agency_id
        $withAid = collect(DB::select("
            SELECT TABLE_NAME AS t
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ? AND COLUMN_NAME = 'agency_id'
            ORDER BY TABLE_NAME
        ", [$dbName]))->pluck('t')->values();

        // جداول مشتركة تُؤخذ كاملة
        $alsoTables = ['model_has_roles','model_has_permissions','role_has_permissions','migrations'];
        $placeholders = implode(',', array_fill(0, count($alsoTables), '?'));
        $existingAlso = collect(DB::select("
            SELECT TABLE_NAME AS t
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME IN ($placeholders)
        ", array_merge([$dbName], $alsoTables)))->pluck('t')->values();

        $allTables = $withAid->merge($existingAlso)->unique()->values();

        file_put_contents($sqlPath, "-- partial dump for agency {$agencyId}\nSET FOREIGN_KEY_CHECKS=0;\n");

        // mysqldump إن توفر
        $dumpBin = null; $dumpAvailable = true;
        try { $dumpBin = $this->resolveBinary('mysqldump'); } catch (\Throwable $e) { $dumpAvailable = false; }

        foreach ($allTables as $t) {
            $hasAgency = $withAid->contains($t);
            $dumpDone = false;

            if ($dumpAvailable) {
                foreach ($this->connectionArgSets() as $connArgs) {
                    // IMPORTANT: إنتاج INSERT فقط (بدون DROP/CREATE/LOCK/TRIGGERS)
                    $common = [
                        '--single-transaction','--quick','--skip-tz-utc',
                        '--no-create-info','--skip-add-drop-table','--skip-triggers',
                        '--skip-lock-tables','--skip-comments','--compact',
                        '--default-character-set=utf8mb4'
                    ];

                    $base = $hasAgency
                        ? array_merge($common, ["--where=agency_id={$agencyId}", $dbName, $t])
                        : array_merge($common, [$dbName, $t]);

                    $args = array_values(array_filter(array_merge([$dumpBin], $connArgs, $base)));
                    $proc = new Process($args, timeout: 300);
                    $proc->run();
                    if ($proc->isSuccessful()) {
                        file_put_contents($sqlPath, "\n-- ".($hasAgency ? "Filtered by agency_id={$agencyId}" : "Full copy (no agency_id)")." for `{$t}`\n", FILE_APPEND);
                        file_put_contents($sqlPath, $proc->getOutput(), FILE_APPEND);
                        file_put_contents($sqlPath, "\n", FILE_APPEND);
                        $dumpDone = true;
                        break;
                    }
                }
            }

            if (!$dumpDone) {
                // الخطة البديلة عبر PDO (INSERT فقط)
                $this->dumpTableViaPdo($t, $agencyId, $sqlPath, $dbName, $hasAgency);
            }
        }

        file_put_contents($sqlPath, "SET FOREIGN_KEY_CHECKS=1;\n", FILE_APPEND);

        // ZIP
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Cannot create zip');
        }
        $zip->addFile($sqlPath, 'payload.sql');
        $this->addPathIfExists($zip, storage_path("app/agencies/{$agencyId}"), "storage_agencies_{$agencyId}");
        $this->addPathIfExists($zip, public_path("uploads/agencies/{$agencyId}"), "public_uploads_agencies_{$agencyId}");
        $zip->close();

        // تنظيف مؤقت
        @unlink($sqlPath);
        @rmdir($workDir);

        // تسجيل
        DB::table('agency_backups')->insert([
            'agency_id'  => $agencyId,
            'filename'   => basename($zipPath),
            'size'       => filesize($zipPath) ?: 0,
            'status'     => 'done',
            'meta'       => json_encode([
                'agency_id'   => $agencyId,
                'tables_filtered_by_agency' => $withAid,
                'tables_full_copy'          => $existingAlso,
                'note'        => 'INSERT-only export (no DDL). Shared tables included fully.',
                'paths'  => [
                    "storage/app/agencies/{$agencyId}",
                    "public/uploads/agencies/{$agencyId}",
                ],
            ], JSON_UNESCAPED_UNICODE),
            'created_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return basename($zipPath);
    }

    /** استعادة نسخة الوكالة (تفريغ بيانات الوكالة فقط + تعقيم SQL من أي DDL) */
    public function restore(int $agencyId, string $zipFilename): void
    {
        $disk   = Storage::disk('agency_backups');
        $zipAbs = $disk->path($zipFilename);
        if (!file_exists($zipAbs)) throw new \InvalidArgumentException('Backup file not found');

        $workDir = $disk->path('__restore_'.Str::random(8));
        mkdir($workDir, 0775, true);

        $zip = new ZipArchive();
        if ($zip->open($zipAbs) !== true) throw new \RuntimeException('Cannot open zip');
        $zip->extractTo($workDir);
        $zip->close();

        $sqlPath = "{$workDir}/payload.sql";
        if (!file_exists($sqlPath)) { $this->rrmdir($workDir); throw new \RuntimeException('payload.sql missing'); }

        $dbName = DB::getDatabaseName();

        // جداول بها agency_id
        $tablesWithAid = collect(DB::select("
            SELECT TABLE_NAME AS t
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ? AND COLUMN_NAME = 'agency_id'
            ORDER BY TABLE_NAME
        ", [$dbName]))->pluck('t')->values();

        // جداول مشتركة يمكن تفريغها قبل الاستيراد لمنع تضارب PK
        $fullCopyBase = ['migrations','model_has_roles','model_has_permissions','role_has_permissions'];
        $ph = implode(',', array_fill(0, count($fullCopyBase), '?'));
        $fullCopyExisting = collect(DB::select("
            SELECT TABLE_NAME AS t
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME IN ($ph)
        ", array_merge([$dbName], $fullCopyBase)))->pluck('t')->values();

        $devTruncateAll = (bool) env('RESTORE_DEV_TRUNCATE_ALL', false);

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // التطوير: TRUNCATE كامل لجداول الوكالة، الإنتاج: حذف agency_id فقط
        foreach ($tablesWithAid as $t) {
            if ($devTruncateAll) {
                DB::table($t)->truncate();
            } else {
                DB::table($t)->where('agency_id', $agencyId)->delete();
            }
        }

        // الجداول المشتركة (إن أردتَ استبدالها بما في النسخة)
        foreach ($fullCopyExisting as $t) {
            DB::table($t)->truncate();
        }

        // تعقيم SQL (حماية إضافية لو وُجد DROP/CREATE/TRIGGER/LOCK بفعل نسخة قديمة)
        $sql = file_get_contents($sqlPath);
        $sql = $this->stripDdlFromSql($sql);

        // استيراد SQL مع إبقاء FK مغلق حتى يكتمل
        $imported = false;
        try {
            $mysqlBin = $this->resolveBinary('mysql');
            foreach ($this->connectionArgSets() as $connArgs) {
                $args  = array_values(array_filter(array_merge([$mysqlBin], $connArgs, [$dbName])));
                $proc = new Process($args, timeout: 300);
                $proc->setInput($sql);
                $proc->run();
                if ($proc->isSuccessful()) { $imported = true; break; }
            }
        } catch (\Throwable $e) {
            // سقوط إلى PDO
        }

        if (!$imported) {
            DB::unprepared($sql);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // استعادة الملفات
        $this->restoreDirIfExists("{$workDir}/storage_agencies_{$agencyId}", storage_path("app/agencies/{$agencyId}"));
        $this->restoreDirIfExists("{$workDir}/public_uploads_agencies_{$agencyId}", public_path("uploads/agencies/{$agencyId}"));

        // تنظيف
        $this->rrmdir($workDir);
    }

    // ====================== Full backup mode (اترك نسختك كما هي) ======================

    public function createFull(string $tag = 'full'): string
    {
        throw new \LogicException('keep your existing createFull() implementation from previous step');
    }

    public function restoreFull(string $zipFilename, bool $restoreProjectFiles = false): void
    {
        throw new \LogicException('keep your existing restoreFull() implementation from previous step');
    }

    // ====================== helpers ======================

    /** يحل مسار mysql/mysqldump */
    private function resolveBinary(string $name): string
    {
        $binDir = (string) env('MYSQL_BIN_PATH', '');
        $binDir = trim($binDir, " \t\n\r\0\x0B\"'\\/");
        if ($binDir !== '') {
            $suffix = str_starts_with(PHP_OS_FAMILY, 'Windows') ? '.exe' : '';
            $candidate = $binDir . DIRECTORY_SEPARATOR . $name . $suffix;
            if (is_file($candidate)) return $candidate;
        }

        $finder = str_starts_with(PHP_OS_FAMILY, 'Windows') ? 'where' : 'which';
        $cmd = Process::fromShellCommandline($finder.' '.$name);
        $cmd->run();
        if ($cmd->isSuccessful()) {
            $path = trim(explode(PHP_EOL, $cmd->getOutput())[0] ?? '');
            if ($path !== '' && is_file($path)) return $path;
        }

        throw new \RuntimeException("$name not found. Checked: ".($binDir ? $binDir : '[no MYSQL_BIN_PATH]').".");
    }

    /** مجموعات وسائط اتصال بديلة */
    private function connectionArgSets(): array
    {
        $mysql = config('database.connections.mysql');
        $host  = (string) ($mysql['host'] ?? '127.0.0.1');
        $port  = (string) ($mysql['port'] ?? '3306');
        $user  = (string) ($mysql['username'] ?? 'root');
        $pass  = (string) ($mysql['password'] ?? '');

        $hosts = array_values(array_unique([$host, '127.0.0.1', 'localhost']));
        $protocols = [null, '--protocol=TCP'];

        $sets = [];
        foreach ($hosts as $h) {
            foreach ($protocols as $proto) {
                $set = ["--host={$h}", "--port={$port}", "--user={$user}"];
                if ($pass !== '') $set[] = "--password={$pass}";
                if ($proto)       $set[] = $proto;
                $sets[] = $set;
            }
        }
        return $sets;
    }

    /** تفريغ عبر PDO */
    private function dumpTableViaPdo(string $table, int $agencyId, string $sqlPath, string $dbName, bool $filterByAgency = true): void
    {
        $pdo = DB::getPdo();
        $cols = $this->describeColumns($dbName, $table);
        if (empty($cols)) return;

        $colNames = array_map(fn($c) => '`'.$c['name'].'`', $cols);
        $colList  = implode(',', $colNames);

        file_put_contents(
            $sqlPath,
            "\n-- Fallback dump via PDO for `{$table}` ".($filterByAgency ? "(filtered agency_id={$agencyId})" : "(full)" ) ."\n",
            FILE_APPEND
        );

        if ($filterByAgency) {
            $stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE `agency_id` = :aid");
            $stmt->execute(['aid' => $agencyId]);
        } else {
            $stmt = $pdo->query("SELECT * FROM `{$table}`");
        }

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $values = [];
            foreach ($cols as $c) {
                $v = $row[$c['name']] ?? null;
                if ($v === null) {
                    $values[] = 'NULL';
                } elseif ($c['is_binary']) {
                    $values[] = '0x'.bin2hex($v);
                } elseif ($c['is_numeric']) {
                    $values[] = (string)$v;
                } else {
                    $values[] = $pdo->quote($v);
                }
            }
            $valuesSql = implode(',', $values);
            file_put_contents($sqlPath, "INSERT INTO `{$table}` ({$colList}) VALUES ({$valuesSql});\n", FILE_APPEND);
        }
    }

    /** توصيف أعمدة الجدول */
    private function describeColumns(string $dbName, string $table): array
    {
        $rows = DB::select("
            SELECT COLUMN_NAME AS name, DATA_TYPE AS type
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION
        ", [$dbName, $table]);

        $numeric = ['int','bigint','smallint','mediumint','tinyint','decimal','float','double','real','bit','year'];
        $binary  = ['binary','varbinary','blob','tinyblob','mediumblob','longblob'];

        $out = [];
        foreach ($rows as $r) {
            $t = strtolower($r->type);
            $out[] = [
                'name'       => $r->name,
                'type'       => $t,
                'is_numeric' => in_array($t, $numeric, true),
                'is_binary'  => in_array($t, $binary, true),
            ];
        }
        return $out;
    }

    /** إزالة أي DDL/Locks من SQL كحماية إضافية */
    private function stripDdlFromSql(string $sql): string
    {
        // احذف CREATE/DROP/ALTER/TRIGGER/LOCK/UNLOCK متعددة الأسطر حتى الفاصلة المنقوطة
        $patterns = [
            '/^\s*DROP\s+TABLE.*?;[\r\n]*/ims',
            '/^\s*CREATE\s+TABLE.*?;[\r\n]*/ims',
            '/^\s*ALTER\s+TABLE.*?;[\r\n]*/ims',
            '/^\s*LOCK\s+TABLES.*?;[\r\n]*/ims',
            '/^\s*UNLOCK\s+TABLES.*?;[\r\n]*/ims',
            '/\/\*!\d{5}\s+TRIGGER.*?END\*\/;[\r\n]*/ims', // تريجرات ضمن تعليقات إصدارات
        ];
        $clean = preg_replace($patterns, '', $sql);
        return $clean ?? $sql;
    }

    private function addPathIfExists(ZipArchive $zip, string $path, string $alias): void
    {
        if (!file_exists($path)) return;

        if (is_dir($path)) {
            $iter = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iter as $file) {
                $rel = $alias.'/'.ltrim(str_replace($path, '', $file->getPathname()), DIRECTORY_SEPARATOR);
                if ($file->isDir()) $zip->addEmptyDir($rel);
                else $zip->addFile($file->getPathname(), $rel);
            }
        } else {
            $zip->addFile($path, $alias.'/'.basename($path));
        }
    }

    private function addPathIfExistsExcluding(ZipArchive $zip, string $path, string $alias, array $excludeAbsPaths): void
    {
        if (!file_exists($path)) return;

        $excludeAbsPaths = array_filter(array_map(fn($p) => rtrim((string)$p, DIRECTORY_SEPARATOR), $excludeAbsPaths));
        $shouldExclude = function (string $abs) use ($excludeAbsPaths): bool {
            foreach ($excludeAbsPaths as $ex) {
                if ($ex !== '' && str_starts_with($abs, $ex)) return true;
            }
            return false;
        };

        if (is_dir($path)) {
            $iter = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iter as $file) {
                $abs = $file->getPathname();
                if ($shouldExclude($abs)) continue;

                $rel = $alias.'/'.ltrim(str_replace($path, '', $abs), DIRECTORY_SEPARATOR);
                if ($file->isDir()) $zip->addEmptyDir($rel);
                else $zip->addFile($abs, $rel);
            }
        } else {
            if (!$shouldExclude($path)) {
                $zip->addFile($path, $alias.'/'.basename($path));
            }
        }
    }

    private function restoreDirIfExists(string $from, string $to): void
    {
        if (!file_exists($from)) return;
        if (!is_dir($to)) mkdir($to, 0775, true);

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($from, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($it as $item) {
            $dst = $to.DIRECTORY_SEPARATOR.$it->getSubPathName();
            if ($item->isDir()) { if (!is_dir($dst)) mkdir($dst, 0775, true); }
            else { copy($item, $dst); }
        }
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $it = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $f) $f->isDir() ? rmdir($f) : unlink($f);
        rmdir($dir);
    }
}
