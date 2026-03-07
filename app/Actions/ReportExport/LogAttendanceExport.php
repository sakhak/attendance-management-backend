<?php

namespace App\Actions\ReportExport;

use App\Models\ReportExports;
use Illuminate\Contracts\Auth\Authenticatable;

class LogAttendanceExport
{
    public function execute(Authenticatable $user , string $type, string $path, int $sizeKb, array $filters): void
    {
        ReportExports::create([
            'user_id'      => $user->id,
            'report_type'  => 'attendance',
            'file_type'    => $type,
            'status'       => 'completed',
            'file_path'    => $path,
            'file_size_kb' => $sizeKb,
            'filters'      => $filters,
        ]);
    }
}