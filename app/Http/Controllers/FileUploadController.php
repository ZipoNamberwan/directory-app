<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FileUploadController extends Controller
{
    public function showForm()
    {
        return view('upload');
    }

    public function uploadFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,png,pdf,docx|max:2048', // Max file size 2MB
        ]);

        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $file->storeAs('uploads', $fileName, 'public'); // Store in storage/app/public/uploads

        return back()->with('success', 'File uploaded successfully!');
    }
}

