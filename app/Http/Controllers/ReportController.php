<?php

namespace App\Http\Controllers;

use App\Models\ReportSubdistrict;
use App\Models\ReportVillage;
use Illuminate\Http\Request;

class ReportController extends Controller
{

    public function index()
    {
        return 'coming soon';
    }

    public function getReport($date, $type, $level, $id)
    {
        $title = '';
        $report = [];

        if ($level == 'kec') {
            $title = 'Report Kecamatan';
            $model = ReportSubdistrict::class;
            $idField = 'subdistrict_id';
            $relation = 'subdistrict';
        } elseif ($level == 'des') {
            $title = 'Report Desa';
            $model = ReportVillage::class;
            $idField = 'village_id';
            $relation = 'village';
        }

        if (isset($model)) {
            $report = $model::where($idField, 'LIKE', $id . '%')
                ->where('type', $type)
                ->where('date', $date)
                ->orderBy($idField)
                ->get()
                ->map(function ($report) use ($relation) {
                    $up = $report->exist + $report->not_exist + $report->not_scope + $report->new;
                    $t = $report->not_update + $up;

                    $report->updated = $up;
                    $report->total = $t;
                    $report->percentage = $t ? $this->safeDivide($up, $t) * 100 : 0;
                    $report->long_code = $report->{$relation}->long_code;
                    $report->name = $report->{$relation}->name;

                    return $report;
                });
        }

        if ($level == 'sls') {
            $title = 'Report SLS';
        }
        return view('report.index', [
            'title' => $title,
            'data' => $report,
            'date' => $date,
            'type' => $type,
            'level' => $level,
            'dateFormatted' => date("j M Y", strtotime($date)),
        ]);
    }

    protected function safeDivide($numerator, $denominator)
    {
        if ($denominator == 0) {
            return "Error: Division by zero!";
        }
        return number_format($numerator / $denominator, 4);
    }
}
