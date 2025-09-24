<?php

namespace App\Http\Controllers;

use App\Models\AssignmentStatus;
use App\Models\Organization;
use App\Models\Regency;
use App\Models\Subdistrict;
use App\Models\User;
use App\Models\Duplicates;
use App\Models\SupplementBusiness;
use App\Models\MarketBusiness;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class MapsController extends Controller
{
    public function index(){
        $user = User::find(Auth::id());
        $organizations = [];
        $regencies = [];
        $subdistricts = [];
        $users = [];
        $isAdmin = false;
        if ($user->hasRole('adminprov')) {
            $regencies = Regency::all();
            $isAdmin = true;
        } else if ($user->hasRole('adminkab')) {
            $users = User::where('organization_id', $user->organization_id)->get();
            $regencies = Regency::where('id', $user->regency_id)->get();
            $subdistricts = Subdistrict::where('regency_id', $user->regency_id)->get();
            $isAdmin = true;
        }


        return view('maps.index', [
            'regencies' => $regencies,
            'subdistricts' => $subdistricts,
            'users' => $users,
            'isAdmin' => $isAdmin,
            'userId' => $user->id,
            'color' => 'secondary',
        ]);
    }

    public function duplicate(){
        $user = User::find(Auth::id());
        $organizations = [];
        $regencies = [];
        $subdistricts = [];
        $users = [];
        $isAdmin = false;
        if ($user->hasRole('adminprov')) {
            $organizations = Organization::all();
            $regencies = Regency::all();
            $isAdmin = true;
        } else if ($user->hasRole('adminkab')) {
            $users = User::where('organization_id', $user->organization_id)->get();
            $regencies = Regency::where('id', $user->regency_id)->get();
            $subdistricts = Subdistrict::where('regency_id', $user->regency_id)->get();
            $isAdmin = true;
        }


        return view('maps.duplicate', [
            'organizations' => $organizations,
            'regencies' => $regencies,
            'subdistricts' => $subdistricts,
            'users' => $users,
            'isAdmin' => $isAdmin,
            'userId' => $user->id,
            'color' => 'secondary',
        ]);
    }

    public function getData(Request $request)
    {
        $user = User::find(Auth::id());

        // base query depending on role
        if ($user->hasRole('adminprov')) {
            $records = Duplicates::query();             
        } elseif ($user->hasRole('adminkab')) {
            $records = Duplicates::where(function ($query) use ($user) {
                $query->where('org_id', $user->organization_id);
            });
        } 

        if ($request->kabkota && $request->kabkota !== '3500') {
            $records->where(function ($query) use ($request) {
                $query->where('org_id', $request->kabkota);
            });
        }

        if ($request->kec) {
            $records->where(function ($query) use ($request) {
                $query->where('subdistrict_1', $request->kec)->orWhere('subdistrict_2', $request->kec);
            });
        }

        if ($request->desa) {
            $records->where(function ($query) use ($request) {
                $query->where('village_1', $request->desa)->orWhere('village_2', $request->desa);
            });
        }

        if ($request->sls) {
            $records->where(function ($query) use ($request) {
                $query->where('sls_1', $request->sls)->orWhere('sls_2', $request->sls);
            });
        }

        if ($request->onlypending == 1) {
            $records->where(function ($query) use ($request) {
                $query->where('is_action', 0);
            });
        }

        

        // sorting
        $orderColumn = $request->get('sort_by', 'id');
        $orderDir = $request->get('sort_dir', 'asc');

        // ✅ get total BEFORE applying pagination
        $totalRecords = (clone $records)->count();

        // ✅ cap total count at 1000
        $total = min($totalRecords, 1000);

        // Progressive loading with page-based pagination
        $perPage = (int) $request->get('size', 20); // Match your paginationSize
        $page = (int) $request->get('page', 1);

        // Calculate offset for the current page
        $offset = ($page - 1) * $perPage;

        // ✅ stop fetching more than 1000 rows
        if ($offset >= 1000) {
            return response()->json([
                "total_records" => $totalRecords,
                "last_page" => (int) ceil($total / $perPage),
                "data" => [],
            ]);
        }

        // Apply pagination with offset and limit
        $data = $records
            // ->with(['user', 'organization', 'project', 'regency', 'subdistrict', 'village', 'sls'])
            ->orderBy($orderColumn, $orderDir)
            ->offset($offset)
            ->limit(min($perPage, 1000 - $offset)) // Don't exceed the 1000 cap
            ->get();

        return response()->json([
            "total_records" => $totalRecords,
            "last_page" => (int) ceil($total / $perPage),
            "data" => $data->toArray(),
        ]);
    }

    public function getDuplicationData(Request $request){
        $data_result=array();
        $message="Anda tidak diizinkan untuk melakukan akses";
        $status=false;

        if ($request->isMethod('post')) {
            $id = $request->post('id');
            $data = Duplicates::find($id);
            $usaha1=array();
            $usaha2=array();
            if($data){
                $source1=$data->sumber_1;
                $source2=$data->sumber_2;
                
                if($data->sumber_1=="SWMAPS"){
                    $usaha1=MarketBusiness::find($data->id_usaha_1);
                } else {
                    $usaha1=SupplementBusiness::find($data->id_usaha_1);
                }

                if($data->sumber_2=="SWMAPS"){
                    $usaha2=MarketBusiness::find($data->id_usaha_2);
                } else {
                    $usaha2=SupplementBusiness::find($data->id_usaha_2); 
                }
                
                if($usaha1 && $usaha2){
                    $message="Data Ditemukan";
                    $status=true;
                    $data_result=array(
                        'usaha1'=>array(
                            'id'=>$usaha1->id,
                            'nama'=>$usaha1->name,
                            'address'=>($usaha1->address ? $usaha1->address : '-'),
                            'description'=>$usaha1->description,
                            'sector'=>$usaha1->sector,
                            'status'=>$usaha1->status,
                            'owner'=>($usaha1->owner ? $usaha1->owner : '-'),
                            'kd_kab'=>$usaha1->regency->name,
                            'kd_kec'=>$usaha1->subdistrict->name,
                            'kd_desa'=>$usaha1->village->name,
                            'kd_sls'=>"[".$usaha1->sls_id."] ".$usaha1->sls->name,
                            'source'=>$source1,
                            'note'=>($usaha1->note ? $usaha1->note : ''),
                        ),
                        'usaha2'=>array(
                            'id'=>$usaha2->id,
                            'nama'=>$usaha2->name,
                            'address'=>($usaha2->address ? $usaha2->address : '-'),
                            'description'=>$usaha2->description,
                            'sector'=>$usaha2->sector,
                            'status'=>$usaha2->status,
                            'owner'=>($usaha2->owner ? $usaha2->owner : '-'),
                            'kd_kab'=>$usaha2->regency->name,
                            'kd_kec'=>$usaha2->subdistrict->name,
                            'kd_desa'=>$usaha2->village->name,
                            'kd_sls'=>"[".$usaha2->sls_id."] ".$usaha2->sls->name,
                            'source'=>$source2,
                            'note'=>($usaha2->note ? $usaha2->note : ''),
                        )
                    );
                } else {
                    $message="Data tidak ditemukan.";
                    $user = User::find(Auth::id());
                    if(!$usaha1){
                        $dup=array();
                        if($data->sumber_1=="SWMAPS"){
                            $dup=MarketBusiness::withTrashed()->find($data->id_usaha_1);
                        } else {
                            $dup=SupplementBusiness::withTrashed()->find($data->id_usaha_1);
                        }
                        if ($dup->trashed()) {
                            $message.=" Usaha 1 sudah pernah dihapus pada ". $dup->deleted_at.". Duplikasi ini bisa diabaikan.";
                            $data->is_action=3;
                            $data->user_action_by=$user->id;
                            $data->user_action_at=date('Y-m-d H:i:s');
                            $data->notes=" Usaha 1 sudah pernah dihapus pada ". $dup->deleted_at;
                            $data->save();
                        }
                    }

                    if(!$usaha2){
                        $dup=array();
                        if($data->sumber_2=="SWMAPS"){
                            $dup=MarketBusiness::withTrashed()->find($data->id_usaha_2);
                        } else {
                            $dup=SupplementBusiness::withTrashed()->find($data->id_usaha_2);
                        }
                        if ($dup->trashed()) {
                            $message.=" Usaha 2 sudah pernah dihapus pada ". $dup->deleted_at.". Duplikasi ini bisa diabaikan.";
                            $data->is_action=3;
                            $data->user_action_by=$user->id;
                            $data->user_action_at=date('Y-m-d H:i:s');
                            $data->notes=" Usaha 2 sudah pernah dihapus pada ". $dup->deleted_at;
                            $data->save();
                        }
                    }
                }
            } else {
                $message="Data tidak ditemukan";
            }
        } 

        $response=array(
            'status'=>$status,
            'message'=>$message,
            'data'=>$data_result
        );
        return response()->json($response);
    }

    public function handleDuplicate(Request $request){
        $data_result=array();
        $message="Anda tidak diizinkan untuk melakukan akses";
        $status=false;
        $user = User::find(Auth::id());

        if ($request->isMethod('post') && $user) {
            $id1 = $request->post('a');
            $id2 = $request->post('b');
            $id=$request->post('d');
            $action = $request->post('c');

            $tmp1=explode("#",$id1);
            $sumber1=$tmp1[0];
            $id1=$tmp1[1];

            $tmp2=explode("#",$id2);
            $sumber2=$tmp2[0];
            $id2=$tmp2[1];

            $data = Duplicates::find($id);
            if($action=='data1'){
                //simpan data 1
                if($data){
                    $data->is_action=1;
                    $data->user_action_by=$user->id;
                    $data->user_action_at=date('Y-m-d H:i:s');
                    $data->usaha_id_deleted=$id2;
                    if($data->save()){
                        $message="Update Data Usaha sudah disimpan";
                        $status=true;
                        if($sumber2=="SWMAPS"){
                            $usaha=MarketBusiness::find($id2);
                            if($usaha){
                                $usaha->deleted_at=date('Y-m-d H:i:s');
                                $usaha->save();
                            }
                        } else {
                            $usaha=SupplementBusiness::find($id2);
                            if($usaha){
                                $usaha->deleted_at=date('Y-m-d H:i:s');
                                $usaha->save();
                            }
                        }
                    }
                }

            } else if($action=='data2'){
                //simpan data 2                
                if($data){
                    $data->is_action=2;
                    $data->user_action_by=$user->id;
                    $data->user_action_at=date('Y-m-d H:i:s');
                    $data->usaha_id_deleted=$id1;
                    if($data->save()){
                        $message="Update Data Usaha sudah disimpan";
                        $status=true;
                        
                        if($sumber1=="SWMAPS"){
                            $usaha=MarketBusiness::find($id1);
                            if($usaha){
                                $usaha->deleted_at=date('Y-m-d H:i:s');
                                $usaha->save();
                            }
                        } else {
                            $usaha=SupplementBusiness::find($id1);
                            if($usaha){
                                $usaha->deleted_at=date('Y-m-d H:i:s');
                                $usaha->save();
                            }
                        }
                    }
                }
            } else if($action=='all'){
                //simpan keduanya
                 if($data){
                    $data->is_action=3;
                    $data->user_action_by=$user->id;
                    $data->user_action_at=date('Y-m-d H:i:s');
                    if($data->save()){
                        $message="Update Data Usaha sudah disimpan";
                        $status=true;
                    }
                }
            }
        } 

        echo json_encode(array(
            'status'=>$status,
            'message'=>$message
        ));
        exit();
    }
}
