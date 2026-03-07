<?php

namespace App\Http\Controllers;

use App\Actions\ReportExport\LogAttendanceExport;
use App\Actions\ReportExport\ExportAttendanceToEXCEL;
use App\Actions\ReportExport\ExportAttendanceToPDF;
use App\Actions\ReportExport\GenerateAttendanceReportData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AttendanceReportExportController extends Controller
{
    public function export(Request $request, string $format) {
        
        if (!in_array($format, ['pdf', 'xlsx'])){
            return response()->json([
                'message' => 'only pdf and xlsx allowed'
            ], 400);
        }

        $user = Auth::user();

        $reportData = app(GenerateAttendanceReportData::class)->execute($request->all()); 

        if($format === 'pdf'){
            $result = app(ExportAttendanceToPDF::class)->execute($reportData);
        } else {
            $result = app(ExportAttendanceToEXCEL::class)->execute($reportData);
        }

    if (Auth::check()) {
        app(LogAttendanceExport::class)->execute(
            $user,
            $format,
            $result['path'],
            $result['size_kb'],
            $request->all()
        );
    }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk->download(
            $result['path'],
            $result['filename']
        );
    }
}