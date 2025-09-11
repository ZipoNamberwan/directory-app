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
use Illuminate\Validation\ValidationException;

class SupplementController extends Controller
{
    public function showSupplementIndexPage()
    {
        $user = User::find(Auth::id());
        $organizations = [];
        $regencies = [];
        $subdistricts = [];
        $users = [];

        if ($user->hasRole('adminprov')) {
            $organizations = Organization::all();
            $regencies = Regency::all();
        } else if ($user->hasRole('adminkab')) {
            $users = User::where('organization_id', $user->organization_id)->get();
            $regencies = Regency::where('id', $user->regency_id)->get();
            $subdistricts = Subdistrict::where('regency_id', $user->regency_id)->get();
        }

        $projectTypes = [
            ['name' => 'SWMAPS Supplement', 'value' => 'swmaps supplement'],
            ['name' => 'Kendedes Mobile', 'value' => 'kendedes mobile'],
        ];

        return view('supplement.index', [
            'organizations' => $organizations,
            'regencies' => $regencies,
            'subdistricts' => $subdistricts,
            'users' => $users,
            'color' => 'success',
            'projectTypes' => $projectTypes,
            // 'canEdit' => $user->hasPermissionTo('edit_business') || $user->hasRole('adminprov'),
            // 'canDelete' => $user->hasPermissionTo('delete_business') || $user->hasRole('adminprov'),
            'canEdit' => false,
            'canDelete' => false,
            'organizationId' => $user->organization_id,
        ]);
    }

    public function showSupplementUploadPage()
    {
        $user = User::find(Auth::id());
        if (!$user->is_allowed_swmaps && !$user->hasRole('adminprov')) {
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
            'file' => 'required|file|mimes:xlsx|max:10240',
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

        // base query depending on role
        if ($user->hasRole('adminprov')) {
            $records = SupplementBusiness::query();
        } elseif ($user->hasRole('adminkab')) {
            $records = SupplementBusiness::where(function ($query) use ($user) {
                $query->where('organization_id', $user->organization_id)
                    ->orWhere('regency_id', $user->organization_id);
            });
        } else {
            $records = SupplementBusiness::where('user_id', $user->id);
        }

        // filters
        if ($request->organization && $request->organization !== 'all') {
            $records->where(function ($query) use ($request) {
                $query->where('organization_id', $request->organization)
                    ->orWhere('regency_id', $request->organization);
            });
        }
        if ($request->user && $request->user !== 'all') {
            $records->where('user_id', $request->user);
        }
        if ($request->projectType && $request->projectType !== 'all') {
            if ($request->projectType === 'swmaps supplement') {
                $records->where('upload_id', '!=', null);
            } else if ($request->projectType === 'kendedes mobile') {
                $records->where('upload_id', '=', null);
            }
        }
        if ($request->statusMatching && $request->statusMatching !== 'all') {
            if ($request->statusMatching === 'failed') {
                $records->where('match_level', 'failed');
            } else if ($request->statusMatching === 'success') {
                $records->where('match_level', '!=', 'failed');
            } else {
                $records->where('match_level', null);
            }
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

        // search
        if ($request->keyword) {
            $search = strtolower($request->keyword);
            $records->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(address) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(note) LIKE ?', ["%{$search}%"]);
            });
        }

        // sorting
        $orderColumn = $request->get('sort_by', 'created_at');
        $orderDir = $request->get('sort_dir', 'desc');

        // ✅ get total BEFORE applying pagination
        $totalRecords = (clone $records)->count();

        // ✅ cap total count at 1000
        $total = min($totalRecords, 1000);

        // Progressive loading with page-based pagination
        $perPage = (int) $request->get('size', 20); // Match your paginationSize
        $page = (int) $request->get('page', 1);

        // Calculate offset for the current page
        $offset = ($page - 1) * $perPage;

        // ✅ stop fetching more than 1000 rows
        if ($offset >= 1000) {
            return response()->json([
                "total_records" => $totalRecords,
                "last_page" => (int) ceil($total / $perPage),
                "data" => [],
            ]);
        }

        // Apply pagination with offset and limit
        $data = $records
            ->with(['user', 'organization', 'project', 'regency', 'subdistrict', 'village', 'sls'])
            ->withCount(['anomalies as not_confirmed_anomalies' => function ($query) {
                $query->where('status', '=', 'notconfirmed');
            }])
            ->orderBy($orderColumn, $orderDir)
            ->offset($offset)
            ->limit(min($perPage, 1000 - $offset)) // Don't exceed the 1000 cap
            ->get();

        return response()->json([
            "total_records" => $totalRecords,
            "last_page" => (int) ceil($total / $perPage),
            "data" => $data->toArray(),
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

    public function confirmDeleteBusiness($id)
    {
        try {
            $business = SupplementBusiness::find($id);

            if (!$business) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data usaha tidak ditemukan atau sudah dihapus oleh user lain'
                ], 404);
            }

            // Check if user has permission to delete business
            $user = User::find(Auth::id());

            if (!$user->hasPermissionTo('delete_business')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk menghapus data usaha'
                ], 403);
            }

            // Delete the business
            $business->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data usaha berhasil dihapus'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus data usaha'
            ], 500);
        }
    }

    public function updateSupplement(Request $request, $id)
    {
        try {
            // Validation rules
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string|max:1000',
                'status' => 'required|in:Tetap,Tidak Tetap',
                'sector' => 'required|string|max:255',
                'owner' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:500',
                'note' => 'nullable|string|max:1000',
            ]);

            // Find the supplement business
            $supplement = SupplementBusiness::find($id);

            if (!$supplement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data usaha tidak ditemukan'
                ], 404);
            }

            // Add is_locked = true to the validated data
            $validated['is_locked'] = true;

            // Update the supplement business
            $supplement->update($validated);

            // Load all required relationships for the response
            $supplement->load([
                'user',
                'organization',
                'project',
                'regency',
                'subdistrict',
                'village',
                'sls'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data usaha berhasil diperbarui',
                'business' => $supplement
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui data usaha'
            ], 500);
        }
    }
}
