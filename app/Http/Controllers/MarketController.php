<?php

namespace App\Http\Controllers;

use App\Models\Market;
use App\Models\MarketUploadStatus;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    public function show()
    {
        $markets = Market::all();
        return view('market.index', ['markets' => $markets]);
    }

    public function getUploadData(Request $request)
    {
        $records = MarketUploadStatus::all();

        $recordsTotal = $records->count();

        $orderColumn = 'firstname';
        $orderDir = 'desc';
        if ($request->order != null) {
            if ($request->order[0]['dir'] == 'asc') {
                $orderDir = 'asc';
            } else {
                $orderDir = 'desc';
            }
            if ($request->order[0]['column'] == '0') {
                $orderColumn = 'firstname';
            } else if ($request->order[0]['column'] == '1') {
                $orderColumn = 'email';
            }
        }

        $searchkeyword = $request->search['value'];
        $data = $records->with(['user', 'market']);
        if ($searchkeyword != null) {
            $data->where(function ($query) use ($searchkeyword) {
                $query->whereRaw('LOWER(filename) LIKE ?', ['%' . strtolower($searchkeyword) . '%']);
                // ->orWhereRaw('LOWER(filename) LIKE ?', ['%' . strtolower($searchkeyword) . '%']);
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
}
