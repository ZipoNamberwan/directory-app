<?php

namespace App\Http\Controllers;

use App\Imports\SupplementBusinessImport;
use App\Jobs\SupplementBusinessExportJob;
use App\Jobs\SupplementUploadNotificationJob;
use App\Models\AssignmentStatus;
use App\Models\Organization;
use App\Models\Regency;
use App\Models\Subdistrict;
use App\Models\SupplementBusiness;
use App\Models\SupplementUploadStatus;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SupplementController extends Controller
{
    public function showSupplementIndexPage()
    {
        $user = User::find(Auth::id());
        $organizations = [];
        $users = [];
        $isAdmin = false;
        if ($user->hasRole('adminprov')) {
            $organizations = Organization::all();
            $isAdmin = true;
        } else if ($user->hasRole('adminkab')) {
            $users = User::where('organization_id', $user->organization_id)->get();
            $isAdmin = true;
        }

        $projectTypes = [
            ['name' => 'SWMAPS Supplement', 'value' => 'swmaps supplement'],
            ['name' => 'Kendedes Mobile', 'value' => 'kendedes mobile'],
        ];

        return view('supplement.index', [
            'organizations' => $organizations,
            'users' => $users,
            'isAdmin' => $isAdmin,
            'userId' => $user->id,
            'color' => 'success',
            'projectTypes' => $projectTypes,
        ]);
    }

    public function showSupplementUploadPage()
    {
        $user = User::find(Auth::id());
        if (!$user->is_allowed_swmaps) {
            return abort(403, 'Upload SW Maps sudah di non-aktifkan. Hubungi admin provinsi untuk mengaktifkan kembali.');
        }
        $users = [];

        if ($user->hasRole('adminprov') || $user->hasRole('adminkab')) {
            $users = User::where('organization_id', $user->organization_id)->get();
        }

        $statuses = SupplementUploadStatus::getStatusValues();

        return view('supplement.upload', [
            'users' => $users,
            'statuses' => $statuses,
            'color' => 'success'
        ]);
    }

    // public function showSupplementDownloadPage()
    // {
    //     $user = Auth::user();
    //     $regencies = [];
    //     $subdistricts = [];

    //     if ($user->regency_id == null) {
    //         $regencies = Regency::all();
    //         $subdistricts = [];
    //     } else {
    //         $regencies = [];
    //         $subdistricts = Subdistrict::where('regency_id', $user->regency_id)->get();
    //     }


    //     return view('supplement.download', [
    //         'user' => $user,
    //         'regencies' => $regencies,
    //         'subdistricts' => $subdistricts,
    //     ]);
    // }

    // public function downloadSupplementProject(Request $request)
    // {
    //     $request->validate([
    //         'village' => 'required|exists:villages,id',
    //     ]);
    //     $files = Storage::files('project_swmaps_desa');

    //     // Find the file that starts with the code
    //     $matchedFile = collect($files)->first(function ($file) use ($request) {
    //         return Str::startsWith(basename($file), $request->village);
    //     });

    //     if (!$matchedFile) {
    //         abort(404, 'File not found');
    //     }

    //     // Return file as download
    //     return Storage::download($matchedFile);
    // }

    public function downloadSupplementProjectAndroid(Request $request)
    {
        return Storage::download('project_swmaps_desa/Project SW Maps 2025.swmz');
    }

    public function downloadSupplementProjectIos(Request $request)
    {
        return Storage::download('project_swmaps_desa/Project SW Maps 2025 IOS.swmt');
    }

    public function showSupplementDownloadPage()
    {
        $user = Auth::user();
        if (!$user->is_allowed_swmaps) {
            return abort(403, 'Download SW Maps sudah di non-aktifkan. Hubungi admin provinsi untuk mengaktifkan kembali.');
        }
        return view('supplement.download-general', ['color' => 'success']);
    }

    public function getUploadStatusData(Request $request)
    {
        $user = User::find(Auth::id());

        $records = null;

        if ($user->hasRole('adminprov')) {
            $records = SupplementUploadStatus::query();
        } else if ($user->hasRole('adminkab')) {
            $records = SupplementUploadStatus::whereHas('user', function ($query) use ($user) {
                $query->where('organization_id', $user->organization_id);
            });
        } else {
            $records = SupplementUploadStatus::where('user_id', $user->id);
        }
        if ($request->status && $request->status !== 'all') {
            $records->where('status', $request->status);
        }
        if ($request->user && $request->user !== 'all') {
            $records->where('user_id', $request->user);
        }

        $recordsTotal = $records->count();

        $orderColumn = 'created_at';
        $orderDir = 'desc';
        if ($request->order != null) {
            if ($request->order[0]['dir'] == 'asc') {
                $orderDir = 'asc';
            } else {
                $orderDir = 'desc';
            }
            if ($request->order[0]['column'] == '2') {
                $orderColumn = 'created_at';
            } else if ($request->order[0]['column'] == '0') {
                $orderColumn = 'user_firstname';
            } else if ($request->order[0]['column'] == '4') {
                $orderColumn = 'message';
            } else if ($request->order[0]['column'] == '3') {
                $orderColumn = 'status';
            }
        }

        $searchkeyword = $request->search['value'];
        // $data = $records->with(['user', 'market']);
        $data = $records;

        if ($searchkeyword != null) {
            $data->where(function ($query) use ($searchkeyword) {
                $query
                    ->orWhereRaw('LOWER(user_firstname) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                    ->orWhereRaw('LOWER(organization_id) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                    ->orWhereRaw('LOWER(area) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                    ->orWhereRaw('LOWER(message) LIKE ?', ['%' . strtolower($searchkeyword) . '%']);
            });
        }
        $recordsFiltered = $data->count();

        if ($orderDir == 'asc') {
            $data = $data->orderBy($orderColumn);
        } else {
            $data = $data->orderByDesc($orderColumn);
        }

        if ($request->length != -1) {
            $data = $data->skip($request->start)
                ->take($request->length)->get();
        }

        $data = $data->values();

        return response()->json([
            "draw" => $request->draw,
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $data
        ]);
    }

    public function upload(Request $request)
    {
        $validateArray = [
            'file' => 'required|file|mimes:xlsx|max:2048',
        ];

        $request->validate($validateArray);
        $user = User::find(Auth::id());

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            $customFileName = $user->firstname . '_' . now()->format('Ymd_His') . '_' . Str::random(4) . '.' . $extension;

            $storedPath = $file->storeAs('/upload_supplement', $customFileName);
            $absolutePath = Storage::path($storedPath);

            $uuid = Str::uuid();
            $status = SupplementUploadStatus::create([
                'id' => $uuid,
                'user_id' => $user->id,
                'organization_id' => $user->organization_id,
                'filename' => $customFileName,
                'status' => 'start',

                'user_firstname' => $user->firstname,
            ]);

            try {
                (new SupplementBusinessImport($uuid))->queue($absolutePath)->chain([
                    new SupplementUploadNotificationJob($uuid),
                ]);
            } catch (Exception $e) {
                $status->update([
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ]);
            }

            return redirect('/suplemen/upload')->with('success-upload', 'File telah diupload, cek status pada tabel di bawah!');
        }

        return redirect('/suplemen/upload')->with('failed-upload', 'File gagal diupload, menyimpan log');
    }

    public function downloadSwmapsExport(Request $request)
    {
        $status = SupplementUploadStatus::find($request->id);
        return Storage::download('upload_supplement/' . $status->filename);
    }

    public function getSupplementData(Request $request)
    {
        $user = User::find(Auth::id());

        $records = null;

        if ($user->hasRole('adminprov')) {
            $records = SupplementBusiness::query();
        } else if ($user->hasRole('adminkab')) {
            $records = SupplementBusiness::where('organization_id', $user->organization_id);
        } else {
            $records = SupplementBusiness::where('user_id', $user->id);
        }

        if ($request->organization && $request->organization !== 'all') {
            $records->where('organization_id', $request->organization);
        }

        if ($request->user && $request->user !== 'all') {
            $records->where('user_id', $request->user);
        }

        if ($request->projectType && $request->projectType !== 'all') {
            $records->whereHas('project', function ($query) use ($request) {
                $query->where('type', $request->projectType);
            });
        }

        $recordsTotal = $records->count();

        $orderColumn = 'created_at';
        $orderDir = 'desc';
        if ($request->order != null) {
            if ($request->order[0]['dir'] == 'asc') {
                $orderDir = 'asc';
            } else {
                $orderDir = 'desc';
            }
            if ($request->order[0]['column'] == '0') {
                $orderColumn = 'name';
            } else if ($request->order[0]['column'] == '1') {
                $orderColumn = 'status';
            } else if ($request->order[0]['column'] == '2') {
                $orderColumn = 'address';
            } else if ($request->order[0]['column'] == '3') {
                $orderColumn = 'description';
            } else if ($request->order[0]['column'] == '4') {
                $orderColumn = 'sector';
            } else if ($request->order[0]['column'] == '5') {
                $orderColumn = 'note';
            }
        }

        $searchkeyword = null;
        if ($request->search != null) {
            $searchkeyword = $request->search['value'];
        }

        $data = $records->with(['user', 'organization', 'project']);
        // $data = $records;

        if ($searchkeyword != null) {
            $data->where(function ($query) use ($searchkeyword) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                    ->orWhereRaw('LOWER(address) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                    ->orWhereRaw('LOWER(description) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                    ->orWhereRaw('LOWER(note) LIKE ?', ['%' . strtolower($searchkeyword) . '%']);
            });
        }
        $recordsFiltered = $data->count();

        if ($orderDir == 'asc') {
            $data = $data->orderBy($orderColumn);
        } else {
            $data = $data->orderByDesc($orderColumn);
        }

        if ($request->length != -1 && $request->length != null) {
            $data = $data->skip($request->start)
                ->take($request->length)->get();
        } else {
            $data = $data->get();
        }

        $data = $data->values();

        return response()->json([
            "draw" => $request->draw,
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $data
        ]);
    }

    public function downloadSupplementBusiness(Request $request)
    {
        $user = User::find(Auth::id());
        $uuid = Str::uuid();

        $status = AssignmentStatus::where('user_id', Auth::id())
            ->where('type', 'download-supplement-business')
            ->whereIn('status', ['start', 'loading'])->first();

        if ($status == null) {
            $status = AssignmentStatus::create([
                'id' => $uuid,
                'status' => 'start',
                'user_id' => $user->id,
                'type' => 'download-supplement-business',
            ]);

            $role = $user->roles->first()->name;

            $organization = $request->organization;
            if ($user->hasRole('adminkab')) {
                $organization = $user->organization_id;
            }

            try {
                SupplementBusinessExportJob::dispatch($organization, $uuid, $role);
            } catch (Exception $e) {
                $status->update([
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ]);

                return redirect('/suplemen')->with('failed-upload', 'Download gagal diproses, log sudah disimpan');
            }
            return redirect('/suplemen')->with('success-upload', 'Download telah di proses, cek status pada tombol status');
        } else {
            return redirect('/suplemen')->with('failed-upload', 'Download tidak diproses karena masih ada proses download yang belum selesai');
        }
    }

    public function deleteBusiness(Request $request, $id)
    {
        $business = SupplementBusiness::find($id);
        if ($business) {
            $business->delete();
            return redirect('/suplemen')->with('success-upload', 'Usaha Telah Dihapus');
        } else {
            return redirect('/suplemen')->with('failed-upload', 'Usaha gagal dihapus, menyimpan log');
        }
    }
}
