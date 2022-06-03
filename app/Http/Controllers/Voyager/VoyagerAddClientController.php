<?php
namespace App\Http\Controllers\Voyager;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use TCG\Voyager\Events\BreadDataUpdated;
use TCG\Voyager\Http\Controllers\VoyagerBaseController;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use TCG\Voyager\Events\BreadDataAdded;
use TCG\Voyager\Facades\Voyager;

class VoyagerAddClientController extends VoyagerBaseController{
    public function store(Request $request){
        $is_exist = Customer::where('email', $request->email)->where('msisdn', $request->msisdn)->where('status','active')->first();
        if($is_exist){
            $redirect = redirect()->back();
            return $redirect->with([
                'message'    =>"Kindly Contact Admin!",
                'alert-type' => 'error',
            ]);
        }


        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('add', app($dataType->model_name));

        // Validate fields with ajax
        $val = $this->validateBread($request->all(), $dataType->addRows)->validate();
        $data = $this->insertUpdateData($request, $slug, $dataType->addRows, new $dataType->model_name());

        event(new BreadDataAdded($dataType, $data));

        $user_data = [
            'msisdn'=>$request->msisdn,
            'email'=>$request->email,
            'name'=>$request->name,
            'password' => bcrypt('secret')
        ];

        $user= User::create($user_data);
        $client = Customer::find($data->id);
        $client->user_id = $user->id;
        $client->save();


        if (!$request->has('_tagging')) {
            if (auth()->user()->can('browse', $data)) {
                $redirect = redirect()->route("voyager.{$dataType->slug}.index");
            } else {
                $redirect = redirect()->back();
            }

            return $redirect->with([
                'message'    => __('voyager::generic.successfully_added_new')." {$dataType->getTranslatedAttribute('display_name_singular')}",
                'alert-type' => 'success',
            ]);
        } else {
            return response()->json(['success' => true, 'data' => $data]);
        }
    }
}
