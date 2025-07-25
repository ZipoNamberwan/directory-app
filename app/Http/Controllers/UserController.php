<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Regency;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
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
        $user = User::find(Auth::id());
        $organizations = [];
        if ($user->hasRole('adminprov')) {
            $organizations = Organization::all();
        }
        return view('user.index', ['organizations' => $organizations]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $organizations = Organization::all();
        return view('user.create', ['user' => null, 'organizations' => $organizations]);
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
            'role' => ['required', Rule::in(['adminprov', 'adminkab', 'pml', 'pcl', 'operator'])],
        ];
        if ($admin->hasRole('adminprov')) {
            $validateArray['organization'] = 'required';
        }

        $validator = Validator::make($request->all(), $validateArray);

        $validator->after(function ($validator) use ($request) {
            if ($request->role === 'adminprov' && $request->organization !== '3500') {
                $validator->errors()->add('organization', 'Harus 3500 untuk adminprov.');
            }

            if ($request->role === 'adminkab' && $request->organization === '3500') {
                $validator->errors()->add('organization', 'Tidak Boleh 3500 untuk adminkab.');
            }
        });

        $validator->validate();

        $user = User::create([
            'firstname' => $request->firstname,
            'email' => $request->email,
            'username' => $request->email,
            'password' => Hash::make($request->password),
            'regency_id' => $admin->hasRole('adminprov') ? ($request->organization != '3500' ? $request->organization : null) : $admin->regency->id,
            'organization_id' => $admin->hasRole('adminprov') ? $request->organization  : $admin->organization->id,
            'must_change_password' => false,
            'is_wilkerstat_user' => $request->has('is_wilkerstat_user') ? $request->is_wilkerstat_user : false,
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
        $organizations = Organization::all();
        return view('user.create', ['user' => User::find($id), 'organizations' => $organizations]);
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
            'role' => ['required', Rule::in(['adminprov', 'adminkab', 'pml', 'pcl', 'operator'])],
        ];
        if ($admin->hasRole('adminprov')) {
            $validateArray['organization'] = 'required';
        }

        $validator = Validator::make($request->all(), $validateArray);

        $validator->after(function ($validator) use ($request) {
            if ($request->role === 'adminprov' && $request->organization !== '3500') {
                $validator->errors()->add('organization', 'Harus 3500 untuk adminprov.');
            }

            if ($request->role === 'adminkab' && $request->organization === '3500') {
                $validator->errors()->add('organization', 'Tidak Boleh 3500 untuk adminkab.');
            }
        });

        $validator->validate();

        $user = User::find($id);
        $user->update([
            'id' =>  (string) Str::uuid(),
            'firstname' => $request->firstname,
            'email' => $request->email,
            'username' => $request->email,
            'regency_id' => $admin->hasRole('adminprov') ? ($request->organization != '3500' ? $request->organization : null) : $admin->regency->id,
            'organization_id' => $admin->hasRole('adminprov') ? $request->organization  : $admin->organization->id,
            'password' => $request->password != $user->password ? Hash::make($request->password) : $user->password,
            'is_wilkerstat_user' => $request->has('is_wilkerstat_user') ? $request->is_wilkerstat_user : false,
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
            $records = User::where(['organization_id' => $user->organization_id]);
        } else if ($user->hasRole('adminprov')) {
            $records = User::query();
        }

        if ($request->role != null && $request->role != '0') {
            $records->role($request->role);
        }
        if ($request->organization != null && $request->organization != '0') {
            $records->where('organization_id', $request->organization);
        }
        if ($request->is_wilkerstat_user != null) {
            if ($request->is_wilkerstat_user == "1") {
                $records->where('is_wilkerstat_user', true);
            }
        }

        $recordsTotal = $records->count();

        $orderColumn = 'firstname';
        $orderDir = 'asc';
        if ($request->order != null) {
            if ($request->order[0]['dir'] == 'asc') {
                $orderDir = 'asc';
            } else {
                $orderDir = 'desc';
            }
            if ($request->order[0]['column'] == '0') {
                $orderColumn = 'firstname';
            } else if ($request->order[0]['column'] == '1') {
                $orderColumn = 'firstname';
            }
        }

        $searchkeyword = $request->search['value'];
        $data = $records->with(['roles', 'regency', 'organization']);
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

    public function getUserByRegency($regency)
    {
        $users = [];
        if ($regency != '3500') {
            $users = User::where('regency_id', $regency)->role(['adminkab', 'pml', 'operator'])->get();
        } else {
            $users = User::where('regency_id', null)->role(['adminprov', 'pml', 'operator'])->get();
        }

        return response()->json($users);
    }
}
