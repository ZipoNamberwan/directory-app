<?php

namespace App\Jobs;

use App\Models\AssignmentStatus;
use App\Models\Market;
use App\Models\Regency;
use App\Models\ReportMarketBusinessMarket;
use App\Models\ReportMarketBusinessRegency;
use App\Models\ReportSupplementBusinessRegency;
use App\Models\ReportTotalBusinessUser;
use App\Models\Sls;
use App\Models\Subdistrict;
use App\Models\Village;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;

class ReportExportJob implements ShouldQueue
{
    use Queueable;

    public $uuid;
    public $date;
    public $type;
    public $marketType;
    public $areaType;

    /**
     * Create a new job instance.
     */
    public function __construct($uuid, $date, $type, $marketType, $areaType)
    {
        $this->uuid = $uuid;
        $this->date = $date;
        $this->type = $type;
        $this->marketType = $marketType;
        $this->areaType = $areaType;

        AssignmentStatus::find($this->uuid)->update(['status' => 'loading',]);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {

            $user = AssignmentStatus::find($this->uuid)->user;

            if (!Storage::exists('dashboard_report')) {
                Storage::makeDirectory('dashboard_report');
            }

            if ($this->type == 'user') {
                $records = [];
                if ($user->hasRole('adminkab')) {
                    $records = ReportTotalBusinessUser::where(['organization_id' => $user->organization_id, 'date' => $this->date]);
                } else if ($user->hasRole('adminprov')) {
                    $records = ReportTotalBusinessUser::where(['date' => $this->date]);
                }

                if (!$records->exists()) {
                    throw new Exception('Report kosong, silakan coba lagi dalam beberapa saat');
                }

                $stream = fopen(Storage::path('/dashboard_report/' . $this->uuid . ".csv"), 'w+');

                $csv = Writer::createFromStream($stream);
                $csv->setDelimiter(',');
                $csv->setEnclosure('"');

                $csv->insertOne(['Nama', 'Satker', 'Sentra Ekonomi', 'Suplemen', 'Total', 'Tanggal']);
                $records
                    ->with(['user'])
                    ->chunk(1000, function ($businesses) use ($csv) {
                        foreach ($businesses as $row) {
                            $csv->insertOne([
                                $row->user->firstname,
                                "[" . $row->user->organization->id . "] " . $row->user->organization->name,
                                $row->market,
                                $row->supplement,
                                $row->total,
                                $row->date,
                            ]);
                        }
                    });

                fclose($stream);
            } else if ($this->type == 'market') {
                $records = [];
                if ($user->hasRole('adminkab')) {
                    $records = ReportMarketBusinessMarket::where(['organization_id' => $user->organization_id, 'date' => $this->date]);
                } else if ($user->hasRole('adminprov')) {
                    $records = ReportMarketBusinessMarket::where(['date' => $this->date]);
                }

                if (!$records->exists()) {
                    throw new Exception('Report kosong, silakan coba lagi dalam beberapa saat');
                }

                $stream = fopen(Storage::path('/dashboard_report/' . $this->uuid . ".csv"), 'w+');

                $csv = Writer::createFromStream($stream);
                $csv->setDelimiter(',');
                $csv->setEnclosure('"');

                $csv->insertOne([
                    'Nama Sentra Ekonomi',
                    'Tipe',
                    'Kabupaten',
                    'Kecamatan',
                    'Desa',
                    'Status Target',
                    'Status Penyelesaian',
                    'Jumlah Muatan',
                    'Tanggal'
                ]);

                $records
                    ->with(['market', 'marketType'])
                    ->chunk(1000, function ($businesses) use ($csv) {
                        foreach ($businesses as $row) {
                            $regency = $row->market->regency;
                            $subdistrict = $row->market->subdistrict ?? null;
                            $village = $row->market->village ?? null;

                            $regencyStr = $regency ? "[" . $regency->id . "] " . $regency->name : '';
                            $subdistrictStr = $subdistrict ? "[" . $subdistrict->short_code . "] " . $subdistrict->name : '';
                            $villageStr = $village ? "[" . $village->short_code . "] " . $village->name : '';

                            $csv->insertOne([
                                $row->market->name,
                                $row->marketType->name,
                                $regencyStr,
                                $subdistrictStr,
                                $villageStr,
                                Market::getTransformedTargetCategoryByValue($row->target_category),
                                Market::getTransformedCompletionStatusByValue($row->completion_status),
                                $row->uploaded,
                                $row->date,
                            ]);
                        }
                    });

                fclose($stream);
            } else if ($this->type == 'regency') {
                if ($this->marketType === 'all') {
                    // Aggregate for all market types
                    $records = ReportMarketBusinessRegency::where('date', $this->date)
                        ->with('organization')
                        ->orderByDesc('date')
                        ->get()
                        ->groupBy('organization_id')
                        ->map(function ($group) {
                            return [
                                'organization_id' => $group->first()->organization_id,
                                'organization' => $group->first()->organization,
                                'total_market' => $group->sum('total_market'),
                                'target' => $group->sum('target'),
                                'non_target' => $group->sum('non_target'),
                                'not_start' => $group->sum('not_start'),
                                'on_going' => $group->sum('on_going'),
                                'done' => $group->sum('done'),
                                'market_have_business' => $group->sum('market_have_business'),
                                'uploaded' => $group->sum('uploaded'),
                            ];
                        })
                        ->sortBy('organization_id');

                    if ($records->isEmpty()) {
                        throw new Exception('Report kosong, silakan coba lagi dalam beberapa saat');
                    }

                    $stream = fopen(Storage::path('/dashboard_report/' . $this->uuid . ".csv"), 'w+');

                    $csv = Writer::createFromStream($stream);
                    $csv->setDelimiter(',');
                    $csv->setEnclosure('"');

                    $csv->insertOne([
                        'Satker',
                        'Total Sentra Ekonomi',
                        'Target',
                        'Bukan Target',
                        'Belum Dimulai',
                        'Sedang Dikerjakan',
                        'Sudah Selesai',
                        'Jumlah Muatan',
                        'Tanggal'
                    ]);

                    foreach ($records as $record) {
                        $csv->insertOne([
                            "[" . $record['organization']->id . "] " . $record['organization']->name,
                            $record['total_market'],
                            $record['target'],
                            $record['non_target'],
                            $record['not_start'],
                            $record['on_going'],
                            $record['done'],
                            $record['uploaded'],
                            $this->date,
                        ]);
                    }

                    fclose($stream);
                } else {
                    // Specific market type
                    $records = ReportMarketBusinessRegency::where('market_type_id', $this->marketType)
                        ->where('date', $this->date)
                        ->with('organization', 'marketType')
                        ->orderByDesc('date')
                        ->limit(39)
                        ->get()
                        ->sortBy('organization_id');

                    if ($records->isEmpty()) {
                        throw new Exception('Report kosong, silakan coba lagi dalam beberapa saat');
                    }

                    $stream = fopen(Storage::path('/dashboard_report/' . $this->uuid . ".csv"), 'w+');

                    $csv = Writer::createFromStream($stream);
                    $csv->setDelimiter(',');
                    $csv->setEnclosure('"');

                    $csv->insertOne([
                        'Satker',
                        'Tipe Sentra Ekonomi',
                        'Total Sentra Ekonomi',
                        'Target',
                        'Bukan Target',
                        'Belum Dimulai',
                        'Sedang Dikerjakan',
                        'Sudah Selesai',
                        'Jumlah Muatan',
                        'Tanggal'
                    ]);

                    foreach ($records as $record) {
                        $csv->insertOne([
                            "[" . $record['organization']->id . "] " . $record['organization']->name,
                            $record['marketType']->name,
                            $record['total_market'],
                            $record['target'],
                            $record['non_target'],
                            $record['not_start'],
                            $record['on_going'],
                            $record['done'],
                            $record['uploaded'],
                            $record['date'],
                        ]);
                    }

                    fclose($stream);
                }
            } else if ($this->type == 'supplement') {
                $records = ReportSupplementBusinessRegency::where('date', $this->date)
                    ->whereIn('type', ['swmaps supplement', 'kendedes mobile'])
                    ->with('organization')
                    ->orderBy('organization_id')
                    ->get();

                if ($records->isEmpty()) {
                    throw new Exception('Report kosong, silakan coba lagi dalam beberapa saat');
                }

                $stream = fopen(Storage::path('/dashboard_report/' . $this->uuid . ".csv"), 'w+');

                $csv = Writer::createFromStream($stream);
                $csv->setDelimiter(',');
                $csv->setEnclosure('"');

                $csv->insertOne([
                    'Satker',
                    'Total Usaha',
                    'Tipe',
                    'Tanggal'
                ]);

                foreach ($records as $record) {
                    $csv->insertOne([
                        "[" . $record['organization']->id . "] " . $record['organization']->name,
                        $record['uploaded'],
                        $record['type'],
                        $this->date,
                    ]);
                }

                fclose($stream);
            } else if ($this->type == 'area') {
                $stream = fopen(Storage::path('/dashboard_report/' . $this->uuid . ".csv"), 'w+');
                $csv = Writer::createFromStream($stream);
                $csv->setDelimiter(',');
                $csv->setEnclosure('"');

                $csv->insertOne([
                    'Kode Wilayah',
                    'Nama Wilayah',
                    'Sentra Ekonomi',
                    'Suplemen',
                    'Total'
                ]);

                $records = [];

                if ($this->areaType == 'province') {
                    $records = Regency::leftJoin('report_regency as r', function ($join) {
                        $join->on('regencies.id', '=', 'r.regency_id')
                            ->whereDate('r.created_at', $this->date);
                    })
                        ->select('regencies.*', DB::raw("COALESCE(SUM(CASE WHEN r.business_type = 'market' THEN r.total END), 0) AS market_total, COALESCE(SUM(CASE WHEN r.business_type = 'supplement' THEN r.total END), 0) AS supplement_total"))
                        ->groupBy('regencies.id')
                        ->get();
                } else if ($this->areaType == 'regency') {
                    if ($user->hasRole('adminprov')) {
                        $records = Subdistrict::query()
                            ->leftJoin('report_subdistrict as r', function ($join) {
                                $join->on('subdistricts.id', '=', 'r.subdistrict_id')
                                    ->whereDate('r.created_at', $this->date);
                            })
                            ->select('subdistricts.*', DB::raw(" COALESCE(SUM(CASE WHEN r.business_type = 'market' THEN r.total END), 0) AS market_total, COALESCE(SUM(CASE WHEN r.business_type = 'supplement' THEN r.total END), 0) AS supplement_total"))
                            ->groupBy('subdistricts.id')
                            ->orderBy('subdistricts.id')
                            ->get();
                    } else if ($user->hasRole('adminkab')) {
                        $records = Subdistrict::query()
                            ->where('subdistricts.id', 'like', $user->organization_id . '%')
                            ->leftJoin('report_subdistrict as r', function ($join) {
                                $join->on('subdistricts.id', '=', 'r.subdistrict_id')
                                    ->whereDate('r.created_at', $this->date);
                            })
                            ->select('subdistricts.*', DB::raw(" COALESCE(SUM(CASE WHEN r.business_type = 'market' THEN r.total END), 0) AS market_total, COALESCE(SUM(CASE WHEN r.business_type = 'supplement' THEN r.total END), 0) AS supplement_total"))
                            ->groupBy('subdistricts.id')
                            ->orderBy('subdistricts.id')
                            ->get();
                    }
                } else if ($this->areaType == 'subdistrict') {
                    if ($user->hasRole('adminprov')) {
                        $records = Village::query()
                            ->leftJoin('report_village as r', function ($join) {
                                $join->on('villages.id', '=', 'r.village_id')
                                    ->whereDate('r.created_at', $this->date);
                            })
                            ->select('villages.*', DB::raw(" COALESCE(SUM(CASE WHEN r.business_type = 'market' THEN r.total END), 0) AS market_total, COALESCE(SUM(CASE WHEN r.business_type = 'supplement' THEN r.total END), 0) AS supplement_total"))
                            ->groupBy('villages.id')
                            ->orderBy('villages.id')
                            ->get();
                    } else if ($user->hasRole('adminkab')) {
                        $records = Village::query()
                            ->where('villages.id', 'like', $user->organization_id . '%')   // show only SLS under the org
                            ->leftJoin('report_village as r', function ($join) {
                                $join->on('villages.id', '=', 'r.village_id')
                                    ->whereDate('r.created_at', $this->date);
                            })
                            ->select('villages.*', DB::raw(" COALESCE(SUM(CASE WHEN r.business_type = 'market' THEN r.total END), 0) AS market_total, COALESCE(SUM(CASE WHEN r.business_type = 'supplement' THEN r.total END), 0) AS supplement_total"))
                            ->groupBy('villages.id')
                            ->orderBy('villages.id')
                            ->get();
                    }
                } else if ($this->areaType == 'village') {
                    if ($user->hasRole('adminprov')) {
                        $records = Sls::query()
                            ->leftJoin('report_sls as r', function ($join) {
                                $join->on('sls.id', '=', 'r.sls_id')
                                    ->whereDate('r.created_at', $this->date);
                            })
                            ->select('sls.*', DB::raw(" COALESCE(SUM(CASE WHEN r.business_type = 'market' THEN r.total END), 0) AS market_total, COALESCE(SUM(CASE WHEN r.business_type = 'supplement' THEN r.total END), 0) AS supplement_total"))
                            ->groupBy('sls.id')
                            ->orderBy('sls.id')
                            ->get();
                    } else if ($user->hasRole('adminkab')) {
                        $records = Sls::query()
                            ->where('sls.id', 'like', $user->organization_id . '%')   // show only SLS under the org
                            ->leftJoin('report_sls as r', function ($join) {
                                $join->on('sls.id', '=', 'r.sls_id')
                                    ->whereDate('r.created_at', $this->date);
                            })
                            ->select('sls.*', DB::raw(" COALESCE(SUM(CASE WHEN r.business_type = 'market' THEN r.total END), 0) AS market_total, COALESCE(SUM(CASE WHEN r.business_type = 'supplement' THEN r.total END), 0) AS supplement_total"))
                            ->groupBy('sls.id')
                            ->orderBy('sls.id')
                            ->get();
                    }
                }

                foreach ($records as $record) {
                    $csv->insertOne([
                        $record['id'],
                        $record['name'],
                        $record['market_total'],
                        $record['supplement_total'],
                        $record['market_total'] + $record['supplement_total'],
                    ]);
                }
            }

            AssignmentStatus::find($this->uuid)->update(['status' => 'success']);
        } catch (Exception $e) {
            AssignmentStatus::find($this->uuid)->update(['status' => 'failed', 'message' => $e->getMessage()]);
        }
    }
}
