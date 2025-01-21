<?php

namespace App\Http\Controllers;

use App\Models\CategorizedBusiness;
use App\Models\Status;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    // public function __construct()
    // {
    //     $this->middleware('auth');
    // }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = User::find(Auth::id());

        if ($user->hasRole('pcl')) {
            $businessBase = CategorizedBusiness::where(['pcl_id' => Auth::id()]);
            $total = (clone $businessBase)->count();
            $not_done = (clone $businessBase)->where(['status_id' => 1])->count();
            $active = (clone $businessBase)->where(['status_id' => 2])->count();
            $not_active = (clone $businessBase)->where(['status_id' => 3])->count();
            $new = (clone $businessBase)->where(['status_id' => 4])->count();
            $statuses = Status::all();

            return view('pcl.index', [
                'total' => $total,
                'not_done' => $not_done,
                'active' => $active,
                'not_active' => $not_active,
                'new' => $new,
                'statuses' => $statuses,
                'type' => 'pcl'
            ]);
        } else if ($user->hasRole('adminkab')) {
            $businessBase = CategorizedBusiness::where(['regency_id' => User::find(Auth::id())->regency_id]);
            $total = (clone $businessBase)->count();
            $not_done = (clone $businessBase)->where(['status_id' => 1])->count();
            $active = (clone $businessBase)->where(['status_id' => 2])->count();
            $not_active = (clone $businessBase)->where(['status_id' => 3])->count();
            $new = (clone $businessBase)->where(['status_id' => 4])->count();
            $statuses = Status::all();

            return view('adminkab.index', [
                'total' => $total,
                'not_done' => $not_done,
                'active' => $active,
                'not_active' => $not_active,
                'new' => $new,
                'statuses' => $statuses,
                'type' => 'adminkab'
            ]);
        }

        return view('pages.dashboard');
    }
}
