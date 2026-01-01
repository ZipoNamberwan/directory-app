<?php

namespace App\Http\Controllers;

use App\Exports\MarketAssignmentExport;
use App\Imports\MarketAssignmentImport;
use App\Jobs\MarketAssignmentNotificationJob;
use App\Models\AssignmentStatus;
use App\Models\Market;
use App\Models\MarketUserPivot;
use App\Models\Organization;
use App\Models\Regency;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class MarketAssignmentController extends Controller
{
    public function showMarketAssignmentForm()
    {
        return view('market.assignment');
    }

    public function showMarketAssignmentPage()
    {
        $user = User::find(Auth::id());
        $organizations = Organization::all();
        $markets = [];
        $users = [];

        if ($user->hasRole('adminkab')) {
            $markets = Market::where('organization_id', $user->organization_id)->get();
            $users = User::where('organization_id', $user->organization_id)->role(['operator', 'pml'])->get();
        }

        return view('market.list-assignment', ['markets' => $markets, 'users' => $users, 'organizations' => $organizations]);
    }

    public function getUserMarketPivot(Request $request)
    {
        $user = User::find(Auth::id());

        $records = null;

        if ($user->hasRole('adminprov')) {
            $records = MarketUserPivot::query();
        } else if ($user->hasRole('adminkab')) {
            $records = MarketUserPivot::whereHas('market', function ($query) use ($user) {
                $query->where('organization_id', $user->organization_id);
            });
        }

        $recordsTotal = $records->count();

        if ($request->organization && $request->organization !== '0') {
            $records->whereHas('user', function ($query) use ($request) {
                $query->where('organization_id', $request->organization);
            });
        }
        if ($request->market && $request->market !== '0') {
            $records->where('market_id', $request->market);
        }
        if ($request->user && $request->user !== '0') {
            $records->where('user_id', $request->user);
        }

        $orderColumn = 'created_at';
        $orderDir = 'desc';

        if (!empty($request->order)) {
            $columnIndex = $request->order[0]['column'];
            $direction = $request->order[0]['dir'] === 'asc' ? 'asc' : 'desc';

            // You can map column index from frontend to actual DB columns here
            switch ($columnIndex) {
                case '0':
                    $orderColumn = 'market_name';
                    break;
                case '1':
                    $orderColumn = 'user_firstname';
                    break;
                default:
                    $orderColumn = 'created_at';
            }

            $orderDir = $direction;
        }

        // Search
        $searchkeyword = $request->search['value'] ?? null;

        if (!empty($searchkeyword)) {
            $records->where(function ($query) use ($searchkeyword) {
                $query->whereRaw('LOWER(market_name) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                    ->orWhereRaw('LOWER(user_firstname) LIKE ?', ['%' . strtolower($searchkeyword) . '%']);
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

    public function showMarketAssignmentCreatePage()
    {
        $user = User::find(Auth::id());
        $markets = [];
        $users = [];

        $markets = Market::where('organization_id', $user->organization_id)->get();
        $users = User::where('organization_id', $user->organization_id)->role(['adminkab', 'adminprov', 'operator', 'pml'])->get();

        return view('market.create', ['markets' => $markets, 'users' => $users]);
    }

    public function storeMarketAssignment(Request $request)
    {
        $validateArray = [
            'market' => 'required',
            'user' => 'required',
        ];

        $request->validate($validateArray);

        $market = Market::find($request->market);
        $userToAssign = User::find($request->user);

        $exists = MarketUserPivot::where('user_id', $userToAssign->id)
            ->where('market_id', $market->id)
            ->exists();

        if (!$exists) {
            MarketUserPivot::create([
                'user_id' => $userToAssign->id,
                'market_id' => $market->id,
                'user_firstname' => $userToAssign->firstname,
                'market_name' => $market->name,
            ]);

            return redirect('/pasar-assignment/list')->with('success-upload', 'Assignment telah ditambahkan!');
        } else {
            return redirect('/pasar-assignment/list')->with('failed-upload', 'Assignment sudah ada, silahkan pilih yang lain');
        }

        return redirect('/pasar-assignment/list')->with('failed-upload', 'Assignment gagal ditambahkan, log sudah disimpan');
    }

    public function uploadMarketAssignment(Request $request)
    {
        $validateArray = [
            'file' => 'required|file|mimes:xlsx|max:2048',
        ];
        $request->validate($validateArray);

        if ($request->hasFile('file')) {

            $status = AssignmentStatus::where('user_id', Auth::id())
                ->where('type', 'upload-market-assignment')
                ->whereIn('status', ['start', 'loading'])->first();

            if ($status == null) {
                $uuid = Str::uuid();

                $file = $request->file('file');
                $extension = $file->getClientOriginalExtension();
                $customFileName = $uuid . '.' . $extension;

                $file->storeAs('/upload_market_assignment', $customFileName);

                $status = AssignmentStatus::create([
                    'id' => $uuid,
                    'user_id' => Auth::id(),
                    'status' => 'start',
                    'type' => 'upload-market-assignment',
                ]);

                try {
                    (new MarketAssignmentImport(User::find(Auth::id())->regency_id, $uuid))->queue('/upload_market_assignment/' . $customFileName)->chain([
                        new MarketAssignmentNotificationJob($uuid),
                    ]);

                    return redirect('/pasar-assignment')->with('success-upload', 'File telah diupload, cek status pada tabel di bawah!');
                } catch (Exception $e) {
                    $status->update([
                        'status' => 'failed',
                        'message' => $e->getMessage(),
                    ]);

                    return redirect('/pasar-assignment')->with('failed-upload', 'File telah diupload, error telah disimpan di log');
                }
            } else {
                return redirect('/pasar-assignment')->with('failed-upload', 'Ada proses upload yang belum selesai, silahkan tunggu hingga proses selesai');
            }
        }
    }

    public function downloadMarketAssignment(Request $request)
    {
        $user = User::find(Auth::id());
        $uuid = Str::uuid();
        return Excel::download(new MarketAssignmentExport($user->organization_id), $uuid . '.xlsx');
    }

    public function deleteMarketAssignment($id)
    {
        $assignment = MarketUserPivot::find($id);

        if ($assignment) {
            $assignment->delete();
            return redirect('/pasar-assignment/list')->with('success-upload', 'Assignment telah dihapus!');
        } else {
            return redirect('/pasar-assignment/list')->with('failed-upload', 'Assignment gagal dihapus, log sudah disimpan');
        }
    }
}
