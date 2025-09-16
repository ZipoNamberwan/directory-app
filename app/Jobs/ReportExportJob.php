<?php

namespace App\Jobs;

use App\Models\AssignmentStatus;
use App\Models\Market;
use App\Models\ReportMarketBusinessMarket;
use App\Models\ReportMarketBusinessRegency;
use App\Models\ReportMarketBusinessUser;
use App\Models\ReportSupplementBusinessRegency;
use App\Models\ReportTotalBusinessUser;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
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
    public function __construct($uuid, $date, $type, $marketType, $areaType = null)
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
                if ($this->areaType == 'province') {
                } else if ($this->areaType == 'regency') {
                } else if ($this->areaType == 'subdistrict') {
                } else if ($this->areaType == 'village') {
                }
            }

            AssignmentStatus::find($this->uuid)->update(['status' => 'success']);
        } catch (Exception $e) {
            AssignmentStatus::find($this->uuid)->update(['status' => 'failed', 'message' => $e->getMessage()]);
        }
    }
}
