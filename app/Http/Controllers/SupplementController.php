<?php

namespace App\Http\Controllers;

use App\Models\Regency;
use App\Models\Subdistrict;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SupplementController extends Controller
{
    public function showDownloadPage()
    {
        $user = Auth::user();
        $regencies = [];
        $subdistricts = [];

        if ($user->regency_id == null) {
            $regencies = Regency::all();
            $subdistricts = [];
        } else {
            $regencies = [];
            $subdistricts = Subdistrict::where('regency_id', $user->regency_id)->get();
        }


        return view('supplement.download', [
            'user' => $user,
            'regencies' => $regencies,
            'subdistricts' => $subdistricts,
        ]);
    }

    public function downloadProject(Request $request)
    {
        $request->validate([
            'village' => 'required|exists:villages,id',
        ]);
        $files = Storage::files('/project_swmaps_desa');

        // Find the file that starts with the code
        $matchedFile = collect($files)->first(function ($file) use ($request) {
            return Str::startsWith(basename($file), $request->village);
        });
    
        if (!$matchedFile) {
            abort(404, 'File not found');
        }
    
        // Return file as download
        return Storage::download($matchedFile);
    }
}
