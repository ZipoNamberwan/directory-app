<?php

namespace App\Http\Controllers;

use App\Models\MarketBusiness;
use App\Models\Organization;
use App\Models\Regency;
use App\Models\SupplementBusiness;
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
            'type' => ['required', 'array', 'min:1'],
            'type.*' => ['in:kendedes,kenarok'],
            'can_edit_business' => 'required',
            'can_delete_business' => 'required',
        ];
        if ($admin->hasRole('adminprov')) {
            $validateArray['organization'] = 'required';
            $validateArray['is_allowed_swmaps'] = 'required';
            $validateArray['can_access_duplicate'] = 'required';
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
            'regency_id' => $admin->hasRole('adminprov') ? ($request->organization != '3500' ? Regency::where('long_code', $request->organization)->first()->id : null) : $admin->regency->id,
            'organization_id' => $admin->hasRole('adminprov') ? $request->organization  : $admin->organization->id,
            'must_change_password' => false,
            'is_kendedes_user' => in_array('kendedes', $request->type ?? []),
            'is_kenarok_user'  => in_array('kenarok', $request->type ?? []),
            'is_wilkerstat_user' => in_array('kenarok', $request->type ?? []),
            'is_allowed_swmaps' => $admin->hasRole('adminprov') ? ($request->is_allowed_swmaps == "1" ? true : false) : false,
        ]);
        $user->assignRoleAllDatabase($request->role);

        // Handle permissions individually
        $permissions = [];

        if ($request->can_edit_business == "1") {
            $permissions[] = 'edit_business';
        }

        if ($request->can_delete_business == "1") {
            $permissions[] = 'delete_business';
        }

        if ($request->can_access_duplicate == "1") {
            $permissions[] = 'can_access_duplicate';
        }

        // Set permissions - always call with true first, then add specific permissions
        $user->setPermissionAllDatabase(true, ...$permissions);

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
            'role' => ['required', Rule::in(['adminprov', 'adminkab', 'pml', 'pcl', 'operator'])],
            'type' => ['required', 'array', 'min:1'],
            'type.*' => ['in:kendedes,kenarok'],
            'change_password' => 'required',
            'can_edit_business' => 'required',
            'can_delete_business' => 'required',
        ];
        if ($request->change_password == "1") {
            $validateArray['password'] = ['required', Password::min(8)->mixedCase()];
        }
        if ($admin->hasRole('adminprov')) {
            $validateArray['organization'] = 'required';
            $validateArray['is_allowed_swmaps'] = 'required';
            $validateArray['can_access_duplicate'] = 'required';
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
            'regency_id' => $admin->hasRole('adminprov') ? ($request->organization != '3500' ? Regency::where('long_code', $request->organization)->first()->id : null) : $admin->regency->id,
            'organization_id' => $admin->hasRole('adminprov') ? $request->organization  : $admin->organization->id,
            'password' => $request->change_password == "1" ? Hash::make($request->password) : $user->password,
            'is_kendedes_user' => in_array('kendedes', $request->type ?? []),
            'is_kenarok_user'  => in_array('kenarok', $request->type ?? []),
            'is_wilkerstat_user' => in_array('kenarok', $request->type ?? []),
            'is_allowed_swmaps' => $admin->hasRole('adminprov') ? ($request->is_allowed_swmaps == "1" ? true : false) : $user->is_allowed_swmaps,
        ]);
        // $user->syncRoles([$request->role]);
        $user->assignRoleAllDatabase($request->role);

        // Handle permissions individually
        $permissions = [];

        if ($request->can_edit_business == "1") {
            $permissions[] = 'edit_business';
        }

        if ($request->can_delete_business == "1") {
            $permissions[] = 'delete_business';
        }

        if ($request->can_access_duplicate == "1") {
            $permissions[] = 'can_access_duplicate';
        }

        // Set permissions - always call with true first, then add specific permissions
        $user->setPermissionAllDatabase(true, ...$permissions);

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
            if (SupplementBusiness::where('user_id', $id)->exists() || MarketBusiness::where('user_id', $id)->exists()) {
                return redirect('/users')->with('error-delete', 'User tidak bisa dihapus karena memiliki data usaha.');
            } else {
                User::destroy($id);
            }
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
        if ($request->is_allowed_duplicate != null) {
            if ($request->is_allowed_duplicate == "1") {
                $records->permission('can_access_duplicate');
            }
        }
        if ($request->organization != null && $request->organization != '0') {
            $records->where('organization_id', $request->organization);
        }
        if ($request->type != null && $request->type != '0' && $request->type != 'all') {
            if ($request->type == 'kenarok') {
                $records->where('is_kenarok_user', true);
            } else if ($request->type == 'kendedes') {
                $records->where('is_kendedes_user', true);
            }
        }
        if ($request->is_allowed_swmaps != null) {
            if ($request->is_allowed_swmaps == "1") {
                $records->where('is_allowed_swmaps', true);
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
        } else {
            $data = $data->get();
        }

        // Add permissions to each user
        $data = $data->map(function ($user) {
            $permissions = [];

            if ($user->hasPermissionTo('edit_business')) {
                $permissions[] = 'edit_business';
            }

            if ($user->hasPermissionTo('delete_business')) {
                $permissions[] = 'delete_business';
            }

            if ($user->hasPermissionTo('can_access_duplicate')) {
                $permissions[] = 'can_access_duplicate';
            }

            $user->permission = implode(', ', $permissions);
            return $user;
        });

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

    public function getUserByOrganization($organization)
    {
        $users = User::where('organization_id', $organization)->get();
        return response()->json($users);
    }


    public function toggleActingContext(Request $request)
    {
        $user = User::find(Auth::id());

        // Check if user has any acting contexts available
        if (!$user->actingContexts()->exists()) {
            return redirect('/')->with('error', 'No acting contexts available for this user.');
        }

        $activeContext = $user->activeActingContext;

        if ($activeContext) {
            // Disable acting context
            $activeContext->update(['active' => false]);
            return redirect('/')->with('success', 'Acting context disabled successfully.');
        } else {
            // Enable acting context (activate the first available context)
            $firstContext = $user->actingContexts()->first();
            if ($firstContext) {
                // Disable all other contexts first
                $user->actingContexts()->update(['active' => false]);
                // Enable the first context
                $firstContext->update(['active' => true]);
                return redirect('/')->with('success', 'Acting context enabled successfully.');
            }
        }

        return redirect('/')->with('error', 'Unable to toggle acting context.');
    }
}
