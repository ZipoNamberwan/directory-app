<?php

namespace App\Http\Controllers;

use App\Jobs\MarketMasterExportJob;
use App\Models\AssignmentStatus;
use App\Models\Market;
use App\Models\MarketType;
use App\Models\Organization;
use App\Models\Regency;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class MarketManagementController extends Controller
{

    public function showMarketManagementPage()
    {
        $user = User::find(Auth::id());
        $organizations = [];
        $isAdmin = false;
        if ($user->hasRole('adminprov')) {
            $organizations = Organization::all();
            $isAdmin = true;
        }

        $targets = Market::getTargetCategoryValues();
        $completionStatus = Market::getCompletionStatusValues();
        $marketTypes = MarketType::all();

        return view('market.management.management', [
            'organizations' => $organizations,
            'isAdmin' => $isAdmin,
            'targets' => $targets,
            'completionStatus' => $completionStatus,
            'marketTypes' => $marketTypes,
        ]);
    }

    public function getMarketManagementData(Request $request)
    {

        $records = null;

        $user = User::find(Auth::id());

        if ($user->hasRole('adminprov')) {
            $records = Market::query();
        } else if ($user->hasRole('adminkab')) {
            $records = Market::where(['organization_id' => $user->organization_id]);
        } else if ($user->hasRole('pml') || $user->hasRole('operator')) {
            $marketIds = $user->markets->pluck('id');
            $records = Market::whereIn('id', $marketIds);
        }

        if ($request->organization != null && $request->organization != '0') {
            $records->where('organization_id', $request->organization);
        }
        if ($request->target != null && $request->target != '0') {
            $records->where('target_category', $request->target);
        }
        if ($request->completion != null && $request->completion != '0') {
            $records->where('completion_status', $request->completion);
        }
        if ($request->marketType != null && $request->marketType != '0') {
            $records->where('market_type_id', $request->marketType);
        }

        $recordsTotal = $records->count();

        $orderColumn = 'name';
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
                $orderColumn = 'village_id';
            } else if ($request->order[0]['column'] == '2') {
                $orderColumn = 'target_category';
            } else if ($request->order[0]['column'] == '3') {
                $orderColumn = 'completion_status';
            }
        }

        $searchkeyword = $request->search['value'];
        $data = $records->with(['regency', 'subdistrict', 'village', 'marketType']);
        if ($searchkeyword != null) {
            $data->where(function ($query) use ($searchkeyword) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                    ->orWhereRaw('LOWER(village_id) LIKE ?', ['%' . strtolower($searchkeyword) . '%']);
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

    public function downloadMarketProject($id)
    {
        $market = Market::find($id);

        try {
            if (!Storage::exists('project_market')) {
                Storage::makeDirectory('project_market');
            }

            $projectFolder = Str::random(8);
            $projectName = $market->name . ' (' . $market->subdistrict->name . ') (' . $market->village->name . ') ' . $market->village_id;
            $newExtension = '.swmz';

            if (!Storage::exists('project_market/' . $projectName . $newExtension)) {
                // Create the directory first
                Storage::makeDirectory("project_market/{$projectFolder}/Projects");

                // Get the base template path
                $sourcePath = 'base_template/Template Updating Muatan Pasar.swm2';

                // Extract extension dynamically (in case it ever changes)
                $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);

                // Rename file to match your desired naming pattern
                $newFileName = $projectName . '.' . $extension;
                $destinationPath = "project_market/{$projectFolder}/Projects/{$newFileName}";

                // Copy file contents to new destination
                if (Storage::exists($sourcePath)) {
                    $contents = Storage::get($sourcePath);

                    Storage::put($destinationPath, $contents);
                }

                // Path to the folder you want to zip
                $folderPath = Storage::path("project_market/{$projectFolder}");

                // Path to save the zip file
                $zipFileName = "{$projectName}{$newExtension}";
                $zipFilePath = Storage::path("project_market/{$zipFileName}");
                // Make sure zip destination exists

                // Create zip
                $zip = new ZipArchive;
                // Create and open the zip file
                if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                    // Get all files and directories in the source folder
                    $files = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($folderPath),
                        RecursiveIteratorIterator::LEAVES_ONLY
                    );

                    // Add all files to the zip
                    foreach ($files as $name => $file) {
                        // Skip directories (they would be added automatically)
                        if (!$file->isDir()) {
                            // Get real and relative path for current file
                            $filePath = $file->getRealPath();
                            $relativePath = substr($filePath, strlen($folderPath) + 1);

                            // Add current file to archive
                            $zip->addFile($filePath, $relativePath);
                        }
                    }

                    // Close the zip file
                    $zip->close();

                    Storage::deleteDirectory("project_market/{$projectFolder}");
                } else {
                    return 'Gagal membuat zip file, log sudah disimpan';
                }
            }
        } catch (Exception $e) {
            return 'Gagal membuat zip file, log sudah disimpan';
        }

        // return Storage::download('project_market/' . $projectName . $newExtension);
        return Storage::download('project_market/' . $projectName . $newExtension, $projectName . $newExtension, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $projectName . $newExtension . '"',
        ]);
    }

    public function deleteMarket($id)
    {
        $user = User::find(Auth::id());
        $market = Market::find($id);
        if ($user->hasRole('adminprov')) {
            $market->delete();
            return redirect('/pasar/manajemen')->with('success-delete', 'Pasar Telah Dihapus');
        } else {
            return redirect('/pasar/manajemen')->with('error-delete', 'Pasar gagal dihapus, menyimpan log');
        }
    }

    public function showMarketCreatePage()
    {
        $regencies = Regency::all();
        $marketTypes = MarketType::all();

        return view('market.management.create-market', [
            'regencies' => $regencies,
            'market' => null,
            'marketTypes' => $marketTypes,
        ]);
    }

    public function showMarketEditPage($id)
    {
        $regencies = Regency::all();
        $marketTypes = MarketType::all();
        $market = Market::find($id);

        return view('market.management.create-market', [
            'regencies' => $regencies,
            'market' => $market,
            'marketTypes' => $marketTypes,
        ]);
    }

    public function storeMarket(Request $request)
    {
        $validateArray = [
            'name' => 'required',
            'regency' => 'required',
            'subdistrict' => 'required',
            'village' => 'required',
            'marketType' => 'required',
        ];

        $request->validate($validateArray);

        Market::create([
            'name' => $request->name,
            'regency_id' => $request->regency,
            'subdistrict_id' => $request->subdistrict,
            'village_id' => $request->village,
            'address' => $request->address,
            'market_type_id' => $request->marketType,
            'organization_id' =>  $request->managedbyprov == "1"  ? 3500 : $request->regency,
        ]);

        return redirect('/pasar/manajemen')->with('success-create', 'Pasar telah ditambah!');
    }

    public function updateMarket(Request $request, $id)
    {
        $validateArray = [
            'name' => 'required',
            'regency' => 'required',
            'subdistrict' => 'required',
            'village' => 'required',
            'marketType' => 'required',
        ];

        $request->validate($validateArray);

        $market = Market::find($id);
        $market->update([
            'name' => $request->name,
            'regency_id' => $request->regency,
            'subdistrict_id' => $request->subdistrict,
            'village_id' => $request->village,
            'address' => $request->address,
            'market_type_id' => $request->marketType,
            'organization_id' =>  $request->managedbyprov == "1"  ? 3500 : $request->regency,
        ]);

        return  redirect('/pasar/manajemen')->with('success-edit', 'Pasar telah diupdate!');
    }

    public function changeMarketTargetCategory(Request $request, $id)
    {
        $validateArray = [
            'target_category' => 'required',
        ];

        $request->validate($validateArray);

        $market = Market::find($id);
        if (!$market) {
            return response()->json(['message' => 'Market not found'], 404);
        }

        $success = $market->update([
            'target_category' => $request->target_category ? 'target' : 'non target',
        ]);

        if ($success) {
            return response()->json(['message' => 'Category updated successfully'], 200);
        } else {
            return response()->json(['message' => 'Failed to update category'], 500);
        }
    }

    public function changeMarketCompletionStatus(Request $request, $id)
    {
        $validateArray = [
            'completion_status' => 'required',
        ];

        $request->validate($validateArray);

        $market = Market::find($id);
        if (!$market) {
            return response()->json(['message' => 'Sentra ekonomi tidak ditemukan'], 404);
        }

        // If requested status is "done" but no businesses exist, reject it
        if ($request->completion_status == 'done' && !$market->businesses()->exists()) {
            return response()->json([
                'message' => 'Tidak bisa tandai selesai, karena tidak ada usaha di pasar tersebut',
                'success' => false,
                'completion_status' => $market->getTransformedCompletionStatusAttribute(),
            ], 422);
        }

        $statusNotCompleted = $market->businesses()->exists() ? 'on going' : 'not start';

        // Only allow setting to "done" if explicitly requested and passes the business check
        $market->completion_status = $request->completion_status ? 'done' : $statusNotCompleted;
        $success = $market->save();

        if ($success) {
            return response()->json([
                'message' => 'Status Penyelesaian Pasar Sukses Diubah',
                'success' => true,
                'completion_status' => $market->getTransformedCompletionStatusAttribute(),
            ], 200);
        } else {
            return response()->json([
                'message' => 'Status Penyelesaian Pasar Gagal Diubah',
                'success' => false,
                'completion_status' => $market->getTransformedCompletionStatusAttribute(),
            ], 500);
        }
    }

    public function downloadMarket(Request $request)
    {
        $user = User::find(Auth::id());
        $uuid = Str::uuid();

        $status = AssignmentStatus::where('user_id', Auth::id())
            ->where('type', 'download-market-master')
            ->whereIn('status', ['start', 'loading'])->first();

        if ($status == null) {
            $status = AssignmentStatus::create([
                'id' => $uuid,
                'status' => 'start',
                'user_id' => $user->id,
                'type' => 'download-market-master',
            ]);

            try {
                MarketMasterExportJob::dispatch($uuid, $user->organization_id);
            } catch (Exception $e) {
                $status->update([
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ]);

                return redirect('/pasar/manajemen')->with('error-delete', 'Download gagal diproses, log sudah disimpan');
            }
            return redirect('/pasar/manajemen')->with('success-edit', 'Download telah di proses, cek status pada tombol status');
        } else {
            return redirect('/pasar/manajemen')->with('error-delete', 'Download tidak diproses karena masih ada proses download yang belum selesai');
        }
    }

    public function savePolygonMarket(Request $request, $id)
    {
        $validateArray = [
            'json' => 'required',
        ];

        $request->validate($validateArray);

        $geojson = json_decode($request->json, true);
        // Convert all coordinates to float
        foreach ($geojson['geometry']['coordinates'][0] as &$point) {
            $point[0] = floatval($point[0]); // longitude
            $point[1] = floatval($point[1]); // latitude
        }
        $validGeoJson = json_encode($geojson);

        $saveFile = Storage::disk('local')->put('market_polygon/' . $id . '.geojson', $validGeoJson);

        if ($saveFile) {
            $market = Market::find($id);
            $success = $market->update([
                'geojson' => $id . ".geojson"
            ]);

            if ($success) {
                return response()->json([
                    'message' => 'Polygon pasar berhasil ditambahkan',
                    'success' => true,
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Polygon pasar gagal ditambahkan',
                    'success' => false,
                ], 200);
            }
        } else {
            return response()->json([
                'message' => 'Polygon pasar gagal ditambahkan',
                'success' => false,
            ], 200);
        }
    }
}
