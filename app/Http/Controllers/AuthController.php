<?php

namespace App\Http\Controllers;

use Session;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Database\Eloquent\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\ValidationException;
use App\Http\Controllers\BaseController as BaseController;


class AuthController extends BaseController
{
    /**
     * 註冊帳號:email,password,name(Auth)
     * 回應
     * 401 USER_INVALID_TOKEN
     * 400 USER_MISSING_FIELD
     * 400 USER_ALREADY_EXISTS
     * 403 USER_PERMISSION_DENY
     * 200 SUCCESS
     */
    public function register(Request $req)
    {
        if(auth()->user()->level == 0)
            return $this->sendErrorResponse(403, "USER_PERMISSION_DENY", [], null);
        $inputs = [
            'email' => $req->email,
            'password' => $req->password,
            'name' => $req->name
        ];
        $rules = [
            'email' => 'required|email',
            'password' => 'required|string',
            'name' => 'required|string'
        ];
        $uniqueEmail = ['email' => 'unique:users'];
        $validator = Validator::make($inputs, $rules);
        $validatorEmail = Validator::make($inputs, $uniqueEmail);
        if($validator->fails())
            return $this->sendErrorResponse(400, "USER_MISSING_FIELD", [], null);
        if($validatorEmail->fails())
            return $this->sendErrorResponse(401, "USER_ALREADY_EXISTS", [], null);

        $user = User::create([
            'name' => $req->name,
            'email' => $req->email,
            'password' => bcrypt($req->password)
        ]);

        $token = $user->createToken('UserToken')->plainTextToken;

        return $this->sendResponse($user, $token);
    }

    /**
     * 登入帳號:email,password
     * 回應
     * 400 USER_MISSING_FIELD
     * 401 USER_NOT_EXISTS
     * 401 PASSWORD_WROND_INPUT_DATA
     * 200 SUCCESS
     */
    public function login(Request $req) {
        $inputs = [
            'email' => $req->email,
            'password' => $req->password,
        ];
        $rules = [
            'email' => 'required|email',
            'password' => 'required|string',
        ];
        $validator = Validator::make($inputs, $rules);
        if($validator->fails())
            return $this->sendErrorResponse(400, "USER_MISSING_FIELD", [], null);
        $user = User::where('email', $req->email)->first();
        if(!$user)
            return $this->sendErrorResponse(401, "USER_NOT_EXISTS", null, null);
        if(!Hash::check($req->password, $user->password))
            return $this->sendErrorResponse(401, "PASSWORD_WROND_INPUT_DATA", null, null);

        $token = $user->createToken('UserToken')->plainTextToken;
        return $this->sendResponse($user, $token);
    }

    /**
     * 登出帳號:(Auth)
     * 回應
     * 401 USER_INVALID_TOKEN
     * 200 SUCCESS
     */
    public function logout(Request $request) {
        auth()->user()->tokens()->delete();
        return $this->sendResponse([], null);
    }

    /**
     * 修改帳號:email, password(Auth)
     * 回應
     * 401 USER_INVALID_TOKEN
     * 403 USER_PERMISSION_DENY
     * 404 USER_NOT_EXISTS
     * 200 SUCCESS
     */
    public function update(Request $req, $id)
    {
        if(auth()->user()->level == 0)
            return $this->sendErrorResponse(403, "USER_PERMISSION_DENY", [], null);
        try {
            $user = User::findOrFail($id);
        }catch(ModelNotFoundException $e){
            return $this->sendErrorResponse(404, "USER_NOT_EXISTS", null, null);
        }

        if(auth()->user()->id == $id || auth()->user()->level == 2){
            if(!is_null($req->email)) $user->email = $req->email;
            if(!is_null($req->password)) $user->password = bcrypt($req->password);
            if(!is_null($req->name)) $user->name = $req->name;
            if(auth()->user()->level == 2) $user->level = $req->level;
            $user->save();
            return $this->sendResponse($user, null);
        }else{
            return $this->sendErrorResponse(403, "USER_PERMISSION_DENY", [auth()->user()->id, $id], null);
        }
    }

    /**
     * 停權帳號:email, password(Auth)
     * 回應
     * 401 USER_INVALID_TOKEN
     * 403 USER_PERMISSION_DENY
     * 404 USER_NOT_EXISTS
     * 200 SUCCESS
     */

    public function ban(Request $req, $id)
    {
        if(auth()->user()->level != 2)
            return $this->sendErrorResponse(403, "USER_PERMISSION_DENY", [], null);
        try {
            $user = User::findOrFail($id);
        }catch(ModelNotFoundException $e){
            return $this->sendErrorResponse(404, "USER_NOT_EXISTS", null, null);
        }

        if(is_null($user->banned_at)){
            $user->banned_at = Carbon::now()->toDateTimeString();
            $user->save();
        }else{
            $user->banned_at = null;
            $user->save();
        }

        return $this->sendResponse($user, null);
    }

    /**
     * 刪除帳號:email, password(Auth)
     * 回應
     * 401 USER_INVALID_TOKEN
     * 403 USER_PERMISSION_DENY
     * 404 USER_NOT_EXISTS
     * 200 SUCCESS
     */
    public function delete(Request $req, $id)
    {
        if(auth()->user()->level == 0)
            return $this->sendErrorResponse(403, "USER_PERMISSION_DENY", [], null);
        try {
            $user = User::findOrFail($id);
        }catch(ModelNotFoundException $e){
            return $this->sendErrorResponse(404, "USER_NOT_EXISTS", null, null);
        }

        $user = User::destroy($id);
        return $this->sendResponse([], null);
    }
    
    /**
     * 查看成員 For FrontEnd
     * 回應
     * 200 SUCCESS
     */
    public function member(Request $req)
    {
        $data = Member::join('users', 'members.user_id', '=', 'users.id')
            ->select('members.title', 'members.position', 'members.education', 'members.research', 'members.office', 'members.tel', 'users.email', 'users.name')
            ->get();
        return $this->sendResponse($data, null);
    }

    /**
     * 查看成員 For Backend
     * 回應
     * 200 SUCCESS
     */
    public function account(Request $req)
    {
        $data = Member::rightJoin('users', 'members.user_id', '=', 'users.id')
            ->get();
        return $this->sendResponse($data, null);
    }
}
