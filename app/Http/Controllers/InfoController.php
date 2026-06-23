<?php

namespace App\Http\Controllers;

use App\Models\Info;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InfoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('info.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('info.create', ['info' => null]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'subtitle'     => 'nullable|string',
            'tags'         => 'nullable|string',
            'type'         => ['required', Rule::in(['announcement', 'faq', 'problem-solution', 'other'])],
            'content'      => 'required|string',
            'is_published' => 'required|boolean',
        ]);

        Info::create([
            'title'        => $request->title,
            'subtitle'     => $request->subtitle,
            'tags'         => $request->tags,
            'type'         => $request->type,
            'content'      => $request->content,
            'is_published' => $request->is_published,
            'published_at' => $request->is_published ? now() : null,
        ]);

        return redirect('/info')->with('success-create', 'Info berhasil ditambahkan!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $info = Info::findOrFail($id);
        return view('info.create', ['info' => $info]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $info = Info::findOrFail($id);
        return view('info.create', ['info' => $info]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'subtitle'     => 'nullable|string',
            'tags'         => 'nullable|string',
            'type'         => ['required', Rule::in(['announcement', 'faq', 'problem-solution', 'other'])],
            'content'      => 'required|string',
            'is_published' => 'required|boolean',
        ]);

        $info = Info::findOrFail($id);
        $info->update([
            'title'        => $request->title,
            'subtitle'     => $request->subtitle,
            'tags'         => $request->tags,
            'type'         => $request->type,
            'content'      => $request->content,
            'is_published' => $request->is_published,
            'published_at' => $request->is_published ? now() : null,
        ]);

        return redirect('/info')->with('success-edit', 'Info berhasil diubah!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Info::findOrFail($id)->delete();
        return redirect('/info')->with('success-delete', 'Info berhasil dihapus!');
    }

    /**
     * Return DataTable JSON data for the index page.
     */
    public function getData(Request $request)
    {
        $records = Info::query();

        // Filter by type
        if ($request->type && $request->type !== '0') {
            $records->where('type', $request->type);
        }

        // Filter by is_published
        if ($request->is_published !== null && $request->is_published !== '') {
            $records->where('is_published', (bool) $request->is_published);
        }

        $recordsTotal = $records->count();

        // Search
        $searchKeyword = $request->input('search.value');
        if ($searchKeyword) {
            $records->where(function ($query) use ($searchKeyword) {
                $query->whereRaw('LOWER(title) LIKE ?', ['%' . strtolower($searchKeyword) . '%'])
                    ->orWhereRaw('LOWER(subtitle) LIKE ?', ['%' . strtolower($searchKeyword) . '%'])
                    ->orWhereRaw('LOWER(tags) LIKE ?', ['%' . strtolower($searchKeyword) . '%']);
            });
        }

        $recordsFiltered = $records->count();

        // Ordering
        $orderColumn = 'updated_at';
        $orderDir    = 'desc';
        if ($request->order) {
            $orderDir = $request->order[0]['dir'] === 'asc' ? 'asc' : 'desc';
            $colIndex = $request->order[0]['column'];
            $orderColumn = match ($colIndex) {
                '0'     => 'title',
                '1'     => 'tags',
                '2'     => 'type',
                '3'     => 'is_published',
                '4'     => 'updated_at',
                default => 'updated_at',
            };
        }

        $data = $records->orderBy($orderColumn, $orderDir);

        if ($request->length != -1) {
            $data = $data->skip($request->start)->take($request->length)->get();
        } else {
            $data = $data->get();
        }

        return response()->json([
            'draw'            => $request->draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }
}
