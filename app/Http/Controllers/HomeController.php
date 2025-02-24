<?php

namespace App\Http\Controllers;

use App\Models\NonSlsBusiness;
use App\Models\ReportProvince;
use App\Models\ReportRegency;
use App\Models\SlsBusiness;
use App\Models\Sls;
use App\Models\Status;
use App\Models\Subdistrict;
use App\Models\User;
use App\Models\Village;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    // public function __construct()
    // {
    //     $this->middleware('auth');
    // }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {

        $user = User::find(Auth::id());

        if ($user->hasRole('pcl')) {
            $businessBase = SlsBusiness::where(['pcl_id' => Auth::id()]);
            $total = (clone $businessBase)->count();
            $not_done = (clone $businessBase)->where(['status_id' => 1])->count();
            $active = (clone $businessBase)->where(['status_id' => 2])->count();
            $not_active = (clone $businessBase)->where(['status_id' => 3])->count();
            $new = (clone $businessBase)->where(['status_id' => 4])->count();
            $statuses = Status::all()->sortBy('order');
            $subdistricts = Subdistrict::where('regency_id', User::find(Auth::id())->regency_id)->get();

            return view('pcl.index', [
                'total' => $total,
                'not_done' => $not_done,
                'active' => $active,
                'not_active' => $not_active,
                'new' => $new,
                'statuses' => $statuses,
                'subdistricts' => $subdistricts
            ]);
        } else if ($user->hasRole('adminkab')) {
            $regency_id = User::find(Auth::id())->regency_id;
            $businessBase = SlsBusiness::where(['regency_id' => $regency_id]);

            // $datetime = new DateTime();
            // $datetime->modify('+2 hours');
            // $today = $datetime->format('Y-m-d');

            $reportTypes = ['sls', 'non_sls'];
            $cardData = [];
            $chartData = [];
            $tableData = [];
            $lastUpdate = '';

            foreach ($reportTypes as $type) {

                $reports = ReportRegency::where([
                    'regency_id' => $regency_id,
                    'type' => $type
                ])->orderByDesc('date')->limit(5)->get();

                $lastUpdate = count($reports) > 0 ? $reports[0]->date : '';

                $updated = count($reports) > 0 ? ($reports[0]->exist + $reports[0]->not_exist + $reports[0]->not_scope + $reports[0]->new) : 0;
                $total = count($reports) > 0 ? ($reports[0]->not_update + $updated) : 0;
                $percentage = $total ? $this->safeDivide($updated, $total) * 100 : 0;

                $cardData[$type] = [
                    'updated' => $updated,
                    'total' => $total,
                    'percentage' => $percentage
                ];

                $percentages = $reports->map(function ($report) {
                    $up = $report ? ($report->exist + $report->not_exist + $report->not_scope + $report->new) : 0;
                    $t = $report ? ($report->not_update + $up) : 0;
                    return $t ? $this->safeDivide($up, $t) * 100 : 0;
                });

                $chartData[$type] = ['data' => ($percentages)->reverse()->values(), 'dates' => ($reports->pluck('date'))->reverse()->values()];

                $tableData[$type]['regency'] = ReportRegency::where([
                    'date' => $lastUpdate,
                    'type' => $type
                ])->orderBy('regency_id')->with('regency')->get()->map(function ($report) {
                    $up = $report ? ($report->exist + $report->not_exist + $report->not_scope + $report->new) : 0;
                    $t = $report ? ($report->not_update + $up) : 0;

                    $report->updated = $up;
                    $report->total = $t;
                    $report->percentage = $t ? $this->safeDivide($up, $t) * 100 : 0;
                    return $report;
                });

                $up = $tableData[$type]['regency']->sum('updated');
                $t = $tableData[$type]['regency']->sum('total');
                $tableData[$type]['province'] = [
                    'code' => '3500',
                    'name' => 'Provinsi Jawa Timur',
                    'updated' => $up,
                    'total' => $t,
                    'percentage' => $t ? $this->safeDivide($up, $t) * 100 : 0
                ];
            }

            $statuses = Status::orderBy('order')->get();
            $subdistricts = Subdistrict::where('regency_id', $regency_id)->get();

            return view('adminkab.index', [
                'cardData' => $cardData,
                'chartData' => $chartData,
                'tableData' => $tableData,
                'lastUpdate' => $lastUpdate,
                'lastUpdateFormatted' => date("j M Y", strtotime($lastUpdate)),
                'statuses' => $statuses,
                'subdistricts' => $subdistricts
            ]);
        } else if ($user->hasRole('pml') || $user->hasRole('operator')) {
            $businessBase = NonSlsBusiness::where(['last_modified_by' => Auth::id()]);
            $total = (clone $businessBase)->count();
            $not_done = (clone $businessBase)->where(['status_id' => 1])->count();
            $active = (clone $businessBase)->where(['status_id' => 2])->count();
            $not_active = (clone $businessBase)->where(['status_id' => 3])->count();
            $new = (clone $businessBase)->where(['status_id' => 4])->count();
            $statuses = Status::all()->sortBy('order');
            $subdistricts = Subdistrict::where('regency_id', User::find(Auth::id())->regency_id)->get();

            return view('pml.index', [
                'total' => $total,
                'not_done' => $not_done,
                'active' => $active,
                'not_active' => $not_active,
                'new' => $new,
                'statuses' => $statuses,
                'subdistricts' => $subdistricts
            ]);
        } else if ($user->hasRole('adminprov')) {
            $regency_id = User::find(Auth::id())->regency_id;
            $businessBase = SlsBusiness::where(['regency_id' => $regency_id]);

            // $datetime = new DateTime();
            // $datetime->modify('+2 hours');
            // $today = $datetime->format('Y-m-d');

            $reportTypes = ['sls', 'non_sls'];
            $cardData = [];
            $chartData = [];
            $tableData = [];
            $lastUpdate = '';

            foreach ($reportTypes as $type) {

                $reports = ReportProvince::where([
                    'type' => $type
                ])->orderByDesc('date')->limit(5)->get();

                $lastUpdate = count($reports) > 0 ? $reports[0]->date : '';

                $updated = count($reports) > 0 ? ($reports[0]->exist + $reports[0]->not_exist + $reports[0]->not_scope + $reports[0]->new) : 0;
                $total = count($reports) > 0 ? ($reports[0]->not_update + $updated) : 0;
                $percentage = $total ? $this->safeDivide($updated, $total) * 100 : 0;

                $cardData[$type] = [
                    'updated' => $updated,
                    'total' => $total,
                    'percentage' => $percentage
                ];

                $percentages = $reports->map(function ($report) {
                    $up = $report ? ($report->exist + $report->not_exist + $report->not_scope + $report->new) : 0;
                    $t = $report ? ($report->not_update + $up) : 0;
                    return $t ? $this->safeDivide($up, $t) * 100 : 0;
                });

                $chartData[$type] = ['data' => ($percentages)->reverse()->values(), 'dates' => ($reports->pluck('date'))->reverse()->values()];

                $tableData[$type]['regency'] = ReportRegency::where([
                    'date' => $lastUpdate,
                    'type' => $type
                ])->orderBy('regency_id')->with('regency')->get()->map(function ($report) {
                    $up = $report ? ($report->exist + $report->not_exist + $report->not_scope + $report->new) : 0;
                    $t = $report ? ($report->not_update + $up) : 0;

                    $report->updated = $up;
                    $report->total = $t;
                    $report->percentage = $t ? $this->safeDivide($up, $t) * 100 : 0;
                    return $report;
                });

                $up = $tableData[$type]['regency']->sum('updated');
                $t = $tableData[$type]['regency']->sum('total');
                $tableData[$type]['province'] = [
                    'code' => '3500',
                    'name' => 'Provinsi Jawa Timur',
                    'updated' => $up,
                    'total' => $t,
                    'percentage' => $t ? $this->safeDivide($up, $t) * 100 : 0
                ];
            }

            $statuses = Status::orderBy('order')->get();
            $subdistricts = Subdistrict::where('regency_id', $regency_id)->get();

            return view('adminprov.index', [
                'cardData' => $cardData,
                'chartData' => $chartData,
                'tableData' => $tableData,
                'lastUpdate' => $lastUpdate,
                'lastUpdateFormatted' => date("j M Y", strtotime($lastUpdate)),
                'statuses' => $statuses,
                'subdistricts' => $subdistricts
            ]);
        }

        return view('pages.dashboard');
    }

    public function getSubdistrict($regency_id)
    {
        $subdistricts = Subdistrict::where('regency_id', $regency_id)->get();

        return response()->json($subdistricts);
    }
    public function getVillage($subdistrict_id)
    {
        $user = User::find(Auth::id());

        $village = [];

        if ($user->hasRole('pcl')) {
            $village = Village::whereIn(
                'id',
                $user->slsBusiness()->select('village_id')->where('village_id', 'like', "{$subdistrict_id}%")->distinct()->pluck('village_id')
            )->get();
        } else if ($user->hasRole('adminprov') || $user->hasRole('adminkab') || $user->hasRole('pml') || $user->hasRole('operator')) {
            $village = Village::where('subdistrict_id', $subdistrict_id)->get();
        }

        return response()->json($village);
    }
    public function getSls($village_id)
    {
        $user = User::find(Auth::id());

        $sls = [];
        if ($user->hasRole('pcl')) {
            $sls = Sls::whereIn(
                'id',
                $user->slsBusiness()->select('sls_id')->where('sls_id', 'like', "{$village_id}%")->distinct()->pluck('sls_id')
            )->get();
        } else if ($user->hasRole('adminprov') || $user->hasRole('adminkab') || $user->hasRole('pml') || $user->hasRole('operator')) {
            $sls = Sls::where('village_id', $village_id)->get();
        }

        return response()->json($sls);
    }
    public function getSlsDirectory($id_sls)
    {
        $user = User::find(Auth::id());
        $business = [];

        if ($user->hasRole('pcl')) {
            $business = $user->slsBusiness()->where('sls_id', '=', $id_sls)->with(['status', 'sls', 'village', 'subdistrict'])->get();
        } else if ($user->hasRole('adminkab') || $user->hasRole('adminprov')) {
            $business = SlsBusiness::where('sls_id', $id_sls)->with(['status', 'sls', 'village', 'subdistrict'])->get();;
        }
        return response()->json($business);
    }

    public function getSlsDirectoryTables(Request $request)
    {
        $user = User::find(Auth::id());
        $records = null;

        if ($user->hasRole('pcl')) {
            $records = $user->slsBusiness();
        } elseif ($user->hasRole('adminkab')) {
            $records = SlsBusiness::where('regency_id', $user->regency_id);
        } else if ($user->hasRole('adminprov')) {
            $records = SlsBusiness::query();
        }

        // Apply filters
        if ($request->status && $request->status !== 'all') {
            $records->where('status_id', $request->status);
        }

        if ($request->regency && $request->regency !== 'all') {
            $records->where('regency_id', $request->regency);
        }

        if ($request->subdistrict && $request->subdistrict !== 'all') {
            $records->where('subdistrict_id', $request->subdistrict);
        }

        if ($request->village && $request->village !== 'all') {
            $records->where('village_id', $request->village);
        }

        if ($request->sls && $request->sls !== 'all') {
            $records->where('sls_id', $request->sls);
        }

        if ($request->assignment !== null) {
            $records->where('pcl_id', $request->assignment == '1' ? '!=' : '=', null);
        }

        $recordsTotal = $records->count();

        $orderColumn = 'sls_id';
        $orderDir = 'desc';
        if ($request->order != null) {
            if ($request->order[0]['dir'] == 'asc') {
                $orderDir = 'asc';
            } else {
                $orderDir = 'desc';
            }
            if ($request->order[0]['column'] == '0') {
                $orderColumn = 'sls_id';
            } else if ($request->order[0]['column'] == '1') {
                $orderColumn = 'name';
            } else if ($request->order[0]['column'] == '2') {
                $orderColumn = 'status_id';
            }
        }

        if ($request->search != null) {
            if ($request->search['value'] != null && $request->search['value'] != '') {
                $searchkeyword = $request->search['value'];
                $records->where(function ($query) use ($searchkeyword) {
                    $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                        ->orWhereRaw('LOWER(owner) LIKE ?', ['%' . strtolower($searchkeyword) . '%']);
                });
            }
        }
        $samples = $records->with(['status', 'sls', 'village', 'subdistrict', 'pcl']);
        $recordsFiltered = $samples->count();

        if ($orderDir == 'asc') {
            $samples = $samples->orderBy($orderColumn);
        } else {
            $samples = $samples->orderByDesc($orderColumn);
        }

        if ($request->length != -1) {
            $samples = $samples->skip($request->start)
                ->take($request->length)->get();
        }

        $samples = $samples->values();

        return response()->json([
            "draw" => $request->draw,
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $samples
        ]);
    }

    public function getNonSlsDirectoryTables(Request $request)
    {
        $user = User::find(Auth::id());
        $records = null;

        if ($user->hasRole('adminkab')) {
            $records = NonSlsBusiness::where('regency_id', $user->regency_id);
        } else if ($user->hasRole('pml') || $user->hasRole('operator')) {
            if ($request->pmltype == 'index') {
                $records = NonSlsBusiness::where('last_modified_by', $user->id);
            } else {
                $records = NonSlsBusiness::where('regency_id', $user->regency_id);
            }
        } else if ($user->hasRole('adminprov')) {
            $records = NonSlsBusiness::query();
        }

        // $regencyId = $user->regency->id ?? $request->regency;
        // if ($request->level === 'regency') {
        //     $records->where('level', 'regency');

        //     if (!empty($request->regency) && $request->regency !== 'all') {
        //         $records->where('regency_id', $request->regency);
        //     }
        // } elseif ($request->level === 'subdistrict') {
        //     $records->where('level', 'subdistrict');

        //     if (!empty($request->regency) && $request->regency !== 'all') {
        //         $records->where('regency_id', $request->regency);
        //     }

        //     if (!empty($request->subdistrict) && $request->subdistrict !== 'all') {
        //         $records->where('subdistrict_id', $request->subdistrict);
        //     } else if ($request->subdistrict == 'all') {
        //         $records->where('subdistrict_id', 'like', "{$regencyId}%");
        //     }
        // } elseif ($request->level === 'village') {
        //     $records->where('level', 'village');

        //     if (!empty($request->regency) && $request->regency !== 'all') {
        //         $records->where('regency_id', $request->regency);
        //     }

        //     if (!empty($request->subdistrict) && $request->subdistrict !== 'all') {
        //         $records->where('subdistrict_id', $request->subdistrict);
        //     } else if ($request->subdistrict == 'all') {
        //         $records->where('village_id', 'like', "{$regencyId}%");
        //     }

        //     if (!empty($request->village) && $request->village !== 'all') {
        //         $records->where('village_id', $request->village);
        //     } else if ($request->village == 'all') {
        //         $records->where('village_id', 'like', "{$regencyId}%");
        //     }
        // }

        $regencyId = $user->regency->id ?? $request->regency;

        $records->where('level', $request->level);

        if (!empty($request->regency) && $request->regency !== 'all') {
            $records->where('regency_id', $request->regency);
        }

        if (in_array($request->level, ['subdistrict', 'village'])) {
            if (!empty($request->subdistrict) && $request->subdistrict !== 'all') {
                $records->where('subdistrict_id', $request->subdistrict);
            } elseif ($request->subdistrict == 'all') {
                $records->where('subdistrict_id', 'like', "{$regencyId}%");
            }
        }

        if ($request->level === 'village') {
            if (!empty($request->village) && $request->village !== 'all') {
                $records->where('village_id', $request->village);
            } elseif ($request->village == 'all') {
                $records->where('village_id', 'like', "{$regencyId}%");
            }
        }

        // Apply filters
        if ($request->status && $request->status !== 'all') {
            $records->where('status_id', $request->status);
        }

        // if ($request->sls && $request->sls !== 'all') {
        //     $records->where('sls_id', $request->sls);
        // }

        // if ($request->assignment !== null) {
        //     $records->where('pml_id', $request->assignment == '1' ? '!=' : '=', null);
        // }

        $recordsTotal = $records->count();

        $orderColumn = 'id';
        $orderDir = 'desc';
        if ($request->order != null) {
            if ($request->order[0]['dir'] == 'asc') {
                $orderDir = 'asc';
            } else {
                $orderDir = 'desc';
            }
            if ($request->order[0]['column'] == '0') {
                $orderColumn = 'sls_id';
            } else if ($request->order[0]['column'] == '1') {
                $orderColumn = 'name';
            } else if ($request->order[0]['column'] == '2') {
                $orderColumn = 'status_id';
            }
        }

        if ($request->search != null) {
            if ($request->search['value'] != null && $request->search['value'] != '') {
                $searchkeyword = $request->search['value'];
                $records->where(function ($query) use ($searchkeyword) {
                    $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                        ->orWhereRaw('LOWER(owner) LIKE ?', ['%' . strtolower($searchkeyword) . '%']);
                });
            }
        }
        $samples = $records->with(['status', 'sls', 'village', 'subdistrict', 'regency', 'pml', 'modifiedBy']);

        $recordsFiltered = $samples->count();

        if ($orderDir == 'asc') {
            $samples = $samples->orderBy($orderColumn);
        } else {
            $samples = $samples->orderByDesc($orderColumn);
        }

        // $sql = vsprintf(str_replace('?', "'%s'", $samples->toSql()), $samples->getBindings());

        // dd($sql);

        if ($request->length != -1) {
            $samples = $samples->skip($request->start ?? 0)
                ->take($request->length ?? 10)->get();
        }

        $samples = $samples->values();

        return response()->json([
            "draw" => $request->draw,
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $samples
        ]);
    }

    // public function updateNonSlsDirectory(Request $request, $id)
    // {
    //     $business = NonSlsBusiness::find($id);
    //     $validationArray = [
    //         'address' => 'required_if:status,2|required_if:status,90',
    //         'name' => 'required_if:status,90',
    //         'owner' => 'required_if:status,90',
    //         'source' => 'required_if:status,90',
    //     ];

    //     if ($business->status_id != 90) {
    //         $validationArray['status'] = 'required';
    //     }

    //     if ($business->sls == null) {
    //         $validationArray['sls'] = 'required_if:status,2';
    //     }
    //     $switchChecked = filter_var($request->switch, FILTER_VALIDATE_BOOLEAN);
    //     if ($switchChecked) {
    //         $validationArray['sls'] = 'required';
    //     }

    //     $request->validate($validationArray);

    //     if ($switchChecked || (!$switchChecked && is_null($business->sls_id))) {
    //         if ($business->level === 'regency') {
    //             $business->subdistrict_id = $request->subdistrict ?: null;
    //         }
    //         if (in_array($business->level, ['regency', 'subdistrict'])) {
    //             $business->village_id = $request->village ?: null;
    //         }
    //         if (in_array($business->level, ['regency', 'subdistrict', 'village'])) {
    //             $business->sls_id = $request->sls ?: null;
    //         }
    //     }

    //     if ($request->status == "2" || $business->status_id == 90) {
    //         $business->address = $request->address;
    //     } else {
    //         $business->address = null;

    //         if ($business->level === 'regency') {
    //             $business->subdistrict_id = null;
    //             $business->village_id = null;
    //             $business->sls_id = null;
    //         } elseif ($business->level === 'subdistrict') {
    //             $business->village_id = null;
    //             $business->sls_id = null;
    //         } elseif ($business->level === 'village') {
    //             $business->sls_id = null;
    //         }
    //     }

    //     $business->last_modified_by = Auth::id();
    //     $business->status_id = $request->status ?? $business->status_id;
    //     if ($business->status_id == 90) {
    //         $business->name = $request->name;
    //         $business->owner = $request->owner;
    //         $business->source = $request->source;
    //     }

    //     $business->save();

    //     return response()->json($business);
    // }

    public function updateNonSlsDirectory(Request $request, $id)
    {
        //not included validation yet
        $business = NonSlsBusiness::find($id);

        if ($request->status) {
            $business->status_id = $request->status;

            if ($request->status == 2 || $request->status == 90) {
                //if found
                if ($request->sls != null && $request->sls != "0") {
                    $business->subdistrict_id = substr($request->sls, 0, 7);
                    $business->village_id = substr($request->sls, 0, 10);
                    $business->sls_id = $request->sls;
                }

                $business->address = $request->address;
            } else {
                //if not found
                if ($business->level === 'regency') {
                    $business->subdistrict_id = null;
                    $business->village_id = null;
                    $business->sls_id = null;
                } elseif ($business->level === 'subdistrict') {
                    $business->village_id = null;
                    $business->sls_id = null;
                } elseif ($business->level === 'village') {
                    $business->sls_id = null;
                }
                $business->address = null;
            }
        } else {
            //if edit new
            if ($request->sls != null && $request->sls != "0") {
                $business->subdistrict_id = substr($request->sls, 0, 7);
                $business->village_id = substr($request->sls, 0, 10);
                $business->sls_id = $request->sls;
            }

            $business->name = $request->name;
            $business->owner = $request->owner;
            $business->address = $request->address;
            $business->source = $request->source;
        }

        $business->save();
        return response()->json($business);
    }

    public function updateSlsDirectory(Request $request, $id)
    {
        if ($request->new === "true") {
            $request->validate([
                'name' => 'required',
            ]);
        } else {
            $request->validate([
                'status' => 'required',
            ]);
        }

        $business = SlsBusiness::find($id);
        if ($request->new === "true") {
            $business->name = $request->name;
        } else {
            $business->status_id = $request->status;
        }

        $business->save();

        return response()->json($business);
    }

    public function addSlsDirectory(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'subdistrict' => 'required',
            'village' => 'required',
            'sls' => 'required',
        ]);

        $user = User::find(Auth::id());

        $business = new SlsBusiness();
        $business->name = $request->name;
        $business->regency_id = $user->hasRole('adminprov') ? $request->regency : User::find(Auth::id())->regency_id;
        $business->subdistrict_id = $request->subdistrict;
        $business->village_id = $request->village;
        $business->sls_id = $request->sls;
        $business->status_id = 90;
        $business->is_new = true;
        $business->pcl_id = $user->hasRole('pcl') ? $user->id : null;
        $business->source = 'Hasil Lapangan';
        $business->save();

        return response()->json($business);
    }

    public function addNonSlsDirectory(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'owner' => 'required',
            'address' => 'required',
            'source' => 'required',
            'sls' => 'required',
        ]);

        $user = User::find(Auth::id());

        $business = new NonSlsBusiness();
        $business->name = $request->name;
        $business->owner = $request->owner;
        $business->address = $request->address;
        $business->source = $request->source;

        $business->initial_address = $request->address;
        $business->is_new = true;
        $business->status_id = 90;
        $business->last_modified_by = $user->id;
        $business->level = 'village';

        $business->regency_id = substr($request->sls, 0, 4);
        $business->subdistrict_id = substr($request->sls, 0, 7);
        $business->village_id = substr($request->sls, 0, 10);
        $business->sls_id = $request->sls;

        $business->save();

        return response()->json($business);
    }

    public function deleteSlsDirectory(string $id)
    {
        $business = SlsBusiness::find($id);
        $business->delete();

        return response()->json($business);
    }

    public function deleteNonSlsDirectory(string $id)
    {
        $business = NonSlsBusiness::find($id);
        $business->delete();

        return response()->json($business);
    }

    protected function safeDivide($numerator, $denominator)
    {
        if ($denominator == 0) {
            return "Error: Division by zero!";
        }
        return number_format($numerator / $denominator, 4);
    }
}
