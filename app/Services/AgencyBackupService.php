<?php

namespace App\Services;

use Illuminate\Support\Facades\{DB, Storage, Auth};
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use ZipArchive;

class AgencyBackupService
{
    /** إنشاء نسخة ZIP تحتوي SQL مفلتر بـ agency_id + ملفات الوكالة */
    public function create(int $agencyId): string
    {
        $disk = Storage::disk('agency_backups');
        if (!is_dir($disk->path(''))) mkdir($disk->path(''), 0775, true);

        $ts       = now()->format('Ymd_His');
        $basename = "agency_{$agencyId}_{$ts}";
        $workDir  = $disk->path("__tmp_{$basename}");
        $sqlPath  = "{$workDir}/payload.sql";
        $zipPath  = $disk->path("{$basename}.zip");

        if (!is_dir($workDir)) mkdir($workDir, 0775, true);

        $dbName = DB::getDatabaseName();
        $tables = collect(DB::select("
            SELECT TABLE_NAME AS t
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ? AND COLUMN_NAME = 'agency_id'
            ORDER BY TABLE_NAME
        ", [$dbName]))->pluck('t')->values();

        file_put_contents($sqlPath, "-- partial dump for agency {$agencyId}\nSET FOREIGN_KEY_CHECKS=0;\n");

        // جرّب اكتشاف binaries، ولو فشل اعتبر mysqldump غير متاح
        $dumpBin = null; $dumpAvailable = true;
        try { $dumpBin = $this->resolveBinary('mysqldump'); }
        catch (\Throwable $e) { $dumpAvailable = false; }

        foreach ($tables as $t) {
            $dumpDone = false;

            if ($dumpAvailable) {
                $errors = [];
                foreach ($this->connectionArgSets() as $connArgs) {
                    $args = array_values(array_filter(array_merge(
                        [$dumpBin],
                        $connArgs,
                        [
                            '--single-transaction',
                            '--skip-lock-tables',
                            '--no-create-db',
                            "--where=agency_id={$agencyId}",
                            $dbName,
                            $t,
                        ]
                    )));
                    $proc = new Process($args, timeout: 300);
                    $proc->run();
                    if ($proc->isSuccessful()) {
                        file_put_contents($sqlPath, $proc->getOutput(), FILE_APPEND);
                        file_put_contents($sqlPath, "\n", FILE_APPEND);
                        $dumpDone = true;
                        break;
                    } else {
                        $errors[] = trim($proc->getErrorOutput());
                        // إذا كان الخطأ 10106 أو 11003 نستمر للمحاولة التالية، وفي النهاية سنسقط للخطة البديلة
                    }
                }
                if (!$dumpDone && !empty($errors)) {
                    // سقوط إلى الخطة البديلة بدون mysqldump
                }
            }

            if (!$dumpDone) {
                // ======= الخطة البديلة: توليد INSERTs عبر PDO =======
                $this->dumpTableViaPdo($t, $agencyId, $sqlPath, $dbName);
            }
        }

        file_put_contents($sqlPath, "SET FOREIGN_KEY_CHECKS=1;\n", FILE_APPEND);

        // إنشاء ZIP
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Cannot create zip');
        }
        $zip->addFile($sqlPath, 'payload.sql');
        $this->addPathIfExists($zip, storage_path("app/agencies/{$agencyId}"), "storage_agencies_{$agencyId}");
        $this->addPathIfExists($zip, public_path("uploads/agencies/{$agencyId}"), "public_uploads_agencies_{$agencyId}");
        $zip->close();

        // تنظيف
        @unlink($sqlPath);
        @rmdir($workDir);

        // تسجيل
        DB::table('agency_backups')->insert([
            'agency_id'  => $agencyId,
            'filename'   => basename($zipPath),
            'size'       => filesize($zipPath) ?: 0,
            'status'     => 'done',
            'meta'       => json_encode([
                'tables' => $tables,
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

    /** استعادة بيانات وكالة من ZIP */
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
        if (!file_exists($sqlPath)) throw new \RuntimeException('payload.sql missing');

        $dbName = DB::getDatabaseName();
        $tables = collect(DB::select("
            SELECT TABLE_NAME AS t
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ? AND COLUMN_NAME = 'agency_id'
            ORDER BY TABLE_NAME
        ", [$dbName]))->pluck('t')->values();

        // حذف بيانات الوكالة
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        foreach ($tables as $t) {
            DB::table($t)->where('agency_id', $agencyId)->delete();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // حاول mysql client أولًا، ثم سقط إلى PDO unprepared
        $imported = false;
        try {
            $mysqlBin = $this->resolveBinary('mysql');
            foreach ($this->connectionArgSets() as $connArgs) {
                $args  = array_values(array_filter(array_merge([$mysqlBin], $connArgs, [$dbName])));
                $proc = new Process($args, timeout: 300);
                $proc->setInput(file_get_contents($sqlPath));
                $proc->run();
                if ($proc->isSuccessful()) { $imported = true; break; }
            }
        } catch (\Throwable $e) {
            // تجاهل، سنجرّب PDO
        }

        if (!$imported) {
            // إستيراد عبر PDO مباشرة
            $sql = file_get_contents($sqlPath);
            DB::unprepared($sql);
        }

        // إعادة الملفات
        $this->restoreDirIfExists("{$workDir}/storage_agencies_{$agencyId}", storage_path("app/agencies/{$agencyId}"));
        $this->restoreDirIfExists("{$workDir}/public_uploads_agencies_{$agencyId}", public_path("uploads/agencies/{$agencyId}"));

        // تنظيف
        $this->rrmdir($workDir);
    }

    /** يحل مسار mysql/mysqldump: من .env ثم PATH */
    private function resolveBinary(string $name): string
    {
        $binDir = rtrim((string) env('MYSQL_BIN_PATH', ''), DIRECTORY_SEPARATOR);
        $suffix = str_starts_with(PHP_OS_FAMILY, 'Windows') ? '.exe' : '';
        if ($binDir !== '') {
            $candidate = $binDir . DIRECTORY_SEPARATOR . $name . $suffix;
            if (file_exists($candidate)) return $candidate;
        }

        $finder = str_starts_with(PHP_OS_FAMILY, 'Windows') ? 'where' : 'which';
        $cmd = Process::fromShellCommandline($finder.' '.$name);
        $cmd->run();
        if ($cmd->isSuccessful()) {
            $path = trim(explode(PHP_EOL, $cmd->getOutput())[0] ?? '');
            if ($path !== '' && file_exists($path)) return $path;
        }

        throw new \RuntimeException("$name not found (set MYSQL_BIN_PATH in .env)");
    }

    /** مجموعات وسائط اتصال بديلة (host/protocol) */
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

    /** الخطة البديلة: توليد INSERTs عبر PDO */
    private function dumpTableViaPdo(string $table, int $agencyId, string $sqlPath, string $dbName): void
    {
        $pdo = DB::getPdo();
        $cols = $this->describeColumns($dbName, $table);
        if (empty($cols)) return;

        $colNames = array_map(fn($c) => '`'.$c['name'].'`', $cols);
        $colList  = implode(',', $colNames);

        file_put_contents($sqlPath, "\n-- Fallback dump via PDO for `{$table}`\n", FILE_APPEND);

        $stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE `agency_id` = :aid");
        $stmt->execute(['aid' => $agencyId]);

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $values = [];
            foreach ($cols as $c) {
                $v = $row[$c['name']] ?? null;
                if ($v === null) {
                    $values[] = 'NULL';
                } elseif ($c['is_binary']) {
                    $values[] = '0x'.bin2hex($v);
                } elseif ($c['is_numeric']) {
                    // أرقام بدون اقتباس
                    $values[] = (string)$v;
                } else {
                    $values[] = $pdo->quote($v);
                }
            }
            $valuesSql = implode(',', $values);
            file_put_contents($sqlPath, "INSERT INTO `{$table}` ({$colList}) VALUES ({$valuesSql});\n", FILE_APPEND);
        }
    }

    /** توصيف أعمدة الجدول (نوع رقمي/ثنائي) */
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
