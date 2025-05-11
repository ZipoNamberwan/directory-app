<?php

namespace App\Http\Controllers;

use App\Helpers\DatabaseSelector;
use App\Models\AssignmentStatus;
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
use Illuminate\Support\Facades\Storage;

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
            $businessBase = SlsBusiness::on(DatabaseSelector::getConnection($user->regency_id))->where(['pcl_id' => Auth::id()]);
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
            $businessBase = NonSlsBusiness::on(DatabaseSelector::getConnection($user->regency_id))->where(['last_modified_by' => Auth::id()]);
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
                SlsBusiness::on(DatabaseSelector::getConnection($user->regency_id))->select('village_id')->where('pcl_id', $user->id)->where('village_id', 'like', "{$subdistrict_id}%")->distinct()->pluck('village_id')
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
                SlsBusiness::on(DatabaseSelector::getConnection($user->regency_id))->select('sls_id')->where('pcl_id', $user->id)->where('sls_id', 'like', "{$village_id}%")->distinct()->pluck('sls_id')
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
            $business = SlsBusiness::on(DatabaseSelector::getConnection($user->regency_id))->where(['sls_id' => $id_sls, 'pcl_id' => $user->id])->with(['status', 'sls', 'village', 'subdistrict'])->get();
        } else if ($user->hasRole('adminkab')) {
            $business = SlsBusiness::on(DatabaseSelector::getConnection($user->regency_id))->where('sls_id', $id_sls)->with(['status', 'sls', 'village', 'subdistrict'])->get();;
        } else if ($user->hasRole('adminprov')) {
            $business = SlsBusiness::on(DatabaseSelector::getConnection(substr(Sls::find($id_sls)->id, 0, 4)))->where('sls_id', $id_sls)->with(['status', 'sls', 'village', 'subdistrict'])->get();;
        }
        return response()->json($business);
    }

    public function getSlsDirectoryTables(Request $request)
    {
        $user = User::find(Auth::id());
        $records = null;

        if ($user->hasRole('pcl')) {
            $records = SlsBusiness::on(DatabaseSelector::getConnection($user->regency_id))->where('pcl_id', $user->id);
        } elseif ($user->hasRole('adminkab')) {
            $records = SlsBusiness::on(DatabaseSelector::getConnection($user->regency_id))->where('regency_id', $user->regency_id);
        } else if ($user->hasRole('adminprov')) {
            if ($request->regency && $request->regency !== 'all') {
                $records = SlsBusiness::on(DatabaseSelector::getConnection($request->regency))
                    ->where('regency_id', $request->regency);
            } else {
                return response()->json([
                    "draw" => $request->draw,
                    "recordsTotal" => 0,
                    "recordsFiltered" => 0,
                    "data" => []
                ]);
            }
        }

        // Apply filters
        if ($request->status && $request->status !== 'all') {
            $records->where('status_id', $request->status);
        }

        if (!$user->hasRole('adminprov') && $request->regency && $request->regency !== 'all') {
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

        $orderColumn = 'id';
        $orderDir = 'asc';
        if ($request->order != null) {
            if ($request->order[0]['dir'] == 'asc') {
                $orderDir = 'asc';
            } else {
                $orderDir = 'desc';
            }
            if ($request->order[0]['column'] == '0') {
                $orderColumn = 'name';
            } else if ($request->order[0]['column'] == '1') {
                $orderColumn = 'sls_id';
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
            $records = NonSlsBusiness::on(DatabaseSelector::getConnection($user->regency_id))->where('regency_id', $user->regency_id);
        } else if ($user->hasRole('pml') || $user->hasRole('operator')) {
            if ($request->pmltype == 'index') {
                $records = NonSlsBusiness::on(DatabaseSelector::getConnection($user->regency_id))->where('last_modified_by', $user->id);
            } else {
                $records = NonSlsBusiness::on(DatabaseSelector::getConnection($user->regency_id))->where('regency_id', $user->regency_id);
            }
        } else if ($user->hasRole('adminprov')) {
            if (empty($request->regency) || $request->regency === 'all') {
                return response()->json([
                    "draw" => $request->draw,
                    "recordsTotal" => 0,
                    "recordsFiltered" => 0,
                    "data" => []
                ]);
            }

            $records = NonSlsBusiness::on(DatabaseSelector::getConnection($request->regency))
                ->where('regency_id', $request->regency);
        }

        $regencyId = $user->regency->id ?? $request->regency;

        if (!empty($request->level) && $request->level !== 'all') {
            $records->where('level', $request->level);
        }

        if (!$user->hasRole('adminprov') && !empty($request->regency) && $request->regency !== 'all') {
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

        $recordsTotal = $records->count();

        $orderColumn = 'id';
        $orderDir = 'asc';
        if ($request->order != null) {
            if ($request->order[0]['dir'] == 'asc') {
                $orderDir = 'asc';
            } else {
                $orderDir = 'desc';
            }
            if ($request->order[0]['column'] == '0') {
                $orderColumn = 'name';
            } else if ($request->order[0]['column'] == '1') {
                if ($request->level === 'regency') {
                    $orderColumn = 'regency_id';
                } else if ($request->level === 'subdistrict') {
                    $orderColumn = 'subdistrict_id';
                } else if ($request->level === 'village') {
                    $orderColumn = 'village_id';
                }
            } else if ($request->order[0]['column'] == '2') {
                $orderColumn = 'status_id';
            }
        }

        if ($request->search != null) {
            if ($request->search['value'] != null && $request->search['value'] != '') {
                $searchkeyword = $request->search['value'];
                $records->where(function ($query) use ($searchkeyword) {
                    $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                        ->orWhereRaw('LOWER(owner) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                        ->orWhereRaw('LOWER(source) LIKE ?', ['%' . strtolower($searchkeyword) . '%']);
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

    public function updateNonSlsDirectory(Request $request, $id)
    {
        //not included validation yet
        $user = User::find(Auth::id());
        $business = null;
        if ($user->regency_id) {
            $business = NonSlsBusiness::on(DatabaseSelector::getConnection($user->regency_id))->find($id);
        } else {
            foreach (DatabaseSelector::getListConnections() as $connection) {
                $business = NonSlsBusiness::on($connection)->find($id);
                if ($business) {
                    break;
                }
            }
        }

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


            $business->address = $request->address;
            $business->source = $request->source;
        }

        $business->name = $request->name;
        $business->owner = $request->owner;
        $business->last_modified_by = Auth::id();
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

        $user = User::find(Auth::id());
        $business = null;
        if ($user->regency_id) {
            $business = SlsBusiness::on(DatabaseSelector::getConnection($user->regency_id))->find($id);
        } else {
            foreach (DatabaseSelector::getListConnections() as $connection) {
                $business = SlsBusiness::on($connection)->find($id);
                if ($business) {
                    break;
                }
            }
        }
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

        $regencyId = $user->hasRole('adminprov') ? $request->regency : User::find(Auth::id())->regency_id;
        $business = SlsBusiness::on(DatabaseSelector::getConnection($regencyId))->create([
            'name' => $request->name,
            'regency_id' => $regencyId,
            'subdistrict_id' => $request->subdistrict,
            'village_id' => $request->village,
            'sls_id' => $request->sls,
            'status_id' => 90,
            'is_new' => true,
            'pcl_id' => $user->hasRole('pcl') ? $user->id : null,
            'source' => 'Hasil Lapangan'
        ]);

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

        $regencyId = substr($request->sls, 0, 4);

        $business = NonSlsBusiness::on(DatabaseSelector::getConnection($regencyId))->create([
            'name' => $request->name,
            'owner' => $request->owner,
            'address' => $request->address,
            'source' => $request->source,
            'initial_address' => $request->address,
            'is_new' => true,
            'status_id' => 90,
            'last_modified_by' => $user->id,
            'level' => 'village',
            'regency_id' => $regencyId,
            'subdistrict_id' => substr($request->sls, 0, 7),
            'village_id' => substr($request->sls, 0, 10),
            'sls_id' => $request->sls
        ]);

        return response()->json($business);
    }

    public function deleteSlsDirectory(string $id)
    {
        $user = User::find(Auth::id());
        $business = null;
        if ($user->regency_id) {
            $business = SlsBusiness::on(DatabaseSelector::getConnection($user->regency_id))->find($id);
        } else {
            foreach (DatabaseSelector::getListConnections() as $connection) {
                $business = SlsBusiness::on($connection)->find($id);
                if ($business) {
                    break;
                }
            }
        }

        if ($business->is_new) {
            $business->delete();
        }

        return response()->json($business);
    }

    public function deleteNonSlsDirectory(string $id)
    {
        $user = User::find(Auth::id());
        $business = null;
        if ($user->regency_id) {
            $business = NonSlsBusiness::on(DatabaseSelector::getConnection($user->regency_id))->find($id);
        } else {
            foreach (DatabaseSelector::getListConnections() as $connection) {
                $business = NonSlsBusiness::on($connection)->find($id);
                if ($business) {
                    break;
                }
            }
        }
        if ($business->is_new) {
            $business->delete();
        }

        return response()->json($business);
    }

    protected function safeDivide($numerator, $denominator)
    {
        if ($denominator == 0) {
            return "Error: Division by zero!";
        }
        return number_format($numerator / $denominator, 4);
    }


    public function getAssignmentStatusData($type, Request $request)
    {
        $user = User::find(Auth::id());

        $records = null;

        if ($user->hasRole('adminprov')) {
            $records = AssignmentStatus::query();
        } else if ($user->hasRole('adminkab')) {
            $records = AssignmentStatus::whereHas('user', function ($query) use ($user) {
                $query->where('organization_id', $user->organization_id);
            });
        } else if ($user->hasRole('pml') || $user->hasRole('operator')) {
            $records = AssignmentStatus::where(['user_id' => $user->id]);
        }

        $type = AssignmentStatus::getTransformedTypeByValue($type);
        if (is_array($type)) {
            $records->whereIn('type', $type);
        } else {
            $records->where('type', $type);
        }

        $recordsTotal = $records->count();

        $orderColumn = 'created_at';
        $orderDir = 'desc';

        if (!empty($request->order)) {
            $columnIndex = $request->order[0]['column'];
            $direction = $request->order[0]['dir'] === 'asc' ? 'asc' : 'desc';

            // You can map column index from frontend to actual DB columns here
            switch ($columnIndex) {
                case '0':
                    $orderColumn = 'id';
                    break;
                case '1':
                    $orderColumn = 'status';
                    break;
                default:
                    $orderColumn = 'created_at';
            }

            $orderDir = $direction;
        }

        // Search
        $searchkeyword = $request->search['value'] ?? null;
        $records = $records->with(['user']);

        if (!empty($searchkeyword)) {
            $records->where(function ($query) use ($searchkeyword) {
                $query->whereHas('user', function ($q) use ($searchkeyword) {
                    $q->whereRaw('LOWER(firstname) LIKE ?', ['%' . strtolower($searchkeyword) . '%']);
                })->whereRaw('LOWER(status) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                    ->orWhereRaw('LOWER(message) LIKE ?', ['%' . strtolower($searchkeyword) . '%']);
            });
        }

        $recordsFiltered = $records->count();

        // Pagination
        if ($request->length != -1) {
            $records->skip($request->start)
                ->take($request->length);
        }

        // Order
        $records->orderBy($orderColumn, $orderDir);

        $data = $records->get();

        return response()->json([
            "draw" => $request->draw,
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $data
        ]);
    }

    public function getAssigmentFile($type, Request $request)
    {
        $status = AssignmentStatus::find($request->id);
        $folder = AssignmentStatus::getFolderDownloadAndTypeByValue($type);

        return Storage::download($folder['name'] . '/' . $status->id . $folder['extension']);
    }
}
