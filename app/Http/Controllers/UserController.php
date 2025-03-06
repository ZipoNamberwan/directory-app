<?php

namespace App\Http\Controllers;

use App\Models\Regency;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

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
        $regencies = Regency::all();
        return view('user.create', ['user' => null, 'regencies' => $regencies]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $admin = User::find(Auth::id());

        $validateArray = [
            'firstname' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', Password::min(8)->mixedCase()],
            'role' => ['required', Rule::in(['adminkab', 'pml', 'pcl', 'operator'])],
        ];
        if ($admin->hasRole('adminprov')) {
            $validateArray['regency'] = 'required';
        }

        $request->validate($validateArray);

        $user = User::create([
            'firstname' => $request->firstname,
            'email' => $request->email,
            'username' => $request->email,
            'password' => Hash::make($request->password),
            'regency_id' => $admin->hasRole('adminprov') ? $request->regency : $admin->regency->id,
            'must_change_password' => false
        ]);
        $user->assignRoleAllDatabase($request->role);

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
        $regencies = Regency::all();
        return view('user.create', ['user' => User::find($id), 'regencies' => $regencies]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $admin = User::find(Auth::id());

        $validateArray = [
            'firstname' => 'required',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($id),
            ],
            'password' => ['required', Password::min(8)->mixedCase()],
            'role' => ['required', Rule::in(['adminkab', 'pml', 'pcl', 'operator'])],
        ];
        if ($admin->hasRole('adminprov')) {
            $validateArray['regency'] = 'required';
        }

        $request->validate($validateArray);

        $user = User::find($id);
        $user->update([
            'id' =>  (string) Str::uuid(),
            'firstname' => $request->firstname,
            'email' => $request->email,
            'username' => $request->email,
            'regency_id' => $admin->hasRole('adminprov') ? $request->regency : $admin->regency->id,
            'password' => $request->password != $user->password ? Hash::make($request->password) : $user->password,
        ]);
        // $user->syncRoles([$request->role]);
        $user->assignRoleAllDatabase($request->role);

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
            $records = User::query();
        }

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
        $data = $records->with(['roles', 'regency']);
        if ($searchkeyword != null) {
            $data->where(function ($query) use ($searchkeyword) {
                $query->whereRaw('LOWER(firstname) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                    ->orWhereRaw('LOWER(email) LIKE ?', ['%' . strtolower($searchkeyword) . '%']);
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

    public function searchUser(Request $request)
    {
        $query = $request->query('search');

        if (!$query) {
            return response()->json([]);
        }

        $users = User::where('firstname', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->orderBy('firstname')
            ->get();

        return response()->json($users);
    }
}
