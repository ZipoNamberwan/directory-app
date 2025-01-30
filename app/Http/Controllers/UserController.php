<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('user.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('user.create', ['user' => null]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'firstname' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
            'role' => ['required', Rule::in(['adminkab', 'pml', 'pcl'])]
        ]);

        $admin = User::find(Auth::user()->id);
        $user = User::create([
            'id' =>  (string) Str::uuid(),
            'firstname' => $request->firstname,
            'email' => $request->email,
            'username' => $request->email,
            'password' => Hash::make($request->password),
            'regency_id' => $admin->regency->id
        ]);
        $user->assignRole($request->role);

        return redirect('/users')->with('success-create', 'Petugas telah ditambah!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return $id;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return view('user.create', ['user' => User::find($id)]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'firstname' => 'required',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($id),
            ],
            'password' => 'required',
            'role' => ['required', Rule::in(['adminkab', 'pml', 'pcl'])]
        ]);

        $admin = User::find(Auth::user()->id);
        $user = User::find($id);
        $user->update([
            'id' =>  (string) Str::uuid(),
            'firstname' => $request->firstname,
            'email' => $request->email,
            'username' => $request->email,
            'password' => $request->password != $user->password ? Hash::make($request->password) : $user->password,
            'regency_id' => $admin->regency->id
        ]);
        $user->syncRoles([$request->role]);

        return redirect('/users')->with('success-edit', 'Petugas telah diubah!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::find(Auth::id());
        $userToDelete = User::find($id);
        if ($user->hasRole('adminkab') && ($userToDelete->hasRole('adminkab') || $userToDelete->hasRole('adminprov'))) {
            return redirect('/users')->with('error-delete', 'Admin tidak bisa dihapus.');
        } else {
            User::destroy($id);
        }

        return redirect('/users')->with('success-edit', 'Petugas telah dihapus!');
    }

    public function getUserData(Request $request)
    {
        $records = null;

        $user = User::find(Auth::id());

        if ($user->hasRole('adminkab')) {
            $records = User::where(['regency_id' => $user->regency_id]);
        } else if ($user->hasRole('adminprov')) {
            $records = User::all();
        }

        $recordsTotal = $records->count();

        $orderColumn = 'name';
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
                $orderColumn = 'email';
            }
        }

        $searchkeyword = $request->search['value'];
        $data = $records->with('roles')->get();
        if ($searchkeyword != null) {
            $data = $data->filter(function ($q) use (
                $searchkeyword
            ) {
                return Str::contains(strtolower($q->name), strtolower($searchkeyword)) ||
                    Str::contains(strtolower($q->email), strtolower($searchkeyword));
            });
        }
        $recordsFiltered = $data->count();

        if ($orderDir == 'asc') {
            $data = $data->sortBy($orderColumn);
        } else {
            $data = $data->sortByDesc($orderColumn);
        }

        if ($request->length != -1) {
            $data = $data->skip($request->start)
                ->take($request->length);
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
