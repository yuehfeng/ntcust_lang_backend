<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Controllers\BaseController as BaseController;

class MemberController extends BaseController
{
    public function create(Request $req, $id)
    {
        if(auth()->user()->level == 0)
            return $this->sendErrorResponse(403, "USER_PERMISSION_DENY", [], null);

        try {
            $user = User::findOrFail($id);
        }catch(ModelNotFoundException $e){
            return $this->sendErrorResponse(404, "USER_NOT_EXISTS", null, null);
        }
        
        $inputs = [
            'title' => $req->title,
            'position' => $req->position,
            'education' => $req->education,
            'research' => $req->research,
            'office' => $req->office,
            'tel' => $req->tel,
        ];
        $rules = [
            'title' => 'required|string',
            'position' => 'required|string',
            'education' => 'required|string',
            'research' => 'required|string',
            'office' => 'required|string',
            'tel' => 'required|string'
        ];
        $validator = Validator::make($inputs, $rules);
        if($validator->fails())
            return $this->sendErrorResponse(400, "USER_MISSING_FIELD", [], null);
        
        $member = Member::create([
            'title' => $req->title,
            'position' => $req->position,
            'education' => $req->education,
            'research' => $req->research,
            'office' => $req->office,
            'tel' => $req->tel,
            'user_id' => $id
        ]);

        return $this->sendResponse($member, null);
    }

    public function update(Request $req, $id)
    {
        if(auth()->user()->level == 0)
            return $this->sendErrorResponse(403, "USER_PERMISSION_DENY", [], null);

        try {
            $user = User::findOrFail($id);
        }catch(ModelNotFoundException $e){
            return $this->sendErrorResponse(404, "USER_NOT_EXISTS", null, null);
        }
        
        $member = Member::where('user_id', $id)->get();
        if(count($member) == 0)
            return $this->sendErrorResponse(404, "MEMBER_NOT_EXISTS", null, null);
        $editing = Member::find($member[0]->id);
        
        $inputs = [
            'title' => $req->title,
            'position' => $req->position,
            'education' => $req->education,
            'research' => $req->research,
            'office' => $req->office,
            'tel' => $req->tel,
        ];
        $rules = [
            'title' => 'required|string',
            'position' => 'required|string',
            'education' => 'required|string',
            'research' => 'required|string',
            'office' => 'required|string',
            'tel' => 'required|string'
        ];
        $validator = Validator::make($inputs, $rules);
        if($validator->fails())
            return $this->sendErrorResponse(400, "USER_MISSING_FIELD", [], null);
        
        $editing->title = $req->title;
        $editing->position = $req->position;
        $editing->education = $req->education;
        $editing->research = $req->research;
        $editing->office = $req->office;
        $editing->tel = $req->tel;
        $editing->save();
        // ---
        $user->updated_at = Carbon::now()->toDateTimeString();
        $user->save();

        return $this->sendResponse($member, null);
    }

    public function delete(Request $req, $id)
    {
        if(auth()->user()->level == 0)
            return $this->sendErrorResponse(403, "USER_PERMISSION_DENY", [], null);

        try {
            $user = User::findOrFail($id);
        }catch(ModelNotFoundException $e){
            return $this->sendErrorResponse(404, "USER_NOT_EXISTS", null, null);
        }
        $member = Member::where('user_id', $id)->get();
        if(count($member) == 0)
            return $this->sendErrorResponse(404, "MEMBER_NOT_EXISTS", null, null);
        $editing = Member::find($member[0]->id);

        Member::destroy($editing->id);

        return $this->sendResponse([], null);
    }
}
