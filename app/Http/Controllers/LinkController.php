<?php

namespace App\Http\Controllers;

use Session;
use App\Http\Controllers\BaseController as BaseController;
use App\Models\Link;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Carbon\Carbon;

class LinkController extends BaseController
{
    public function read(Request $req)
    {
        $group = is_null($req->group)?false:$req->group;
        if((boolean)$group){
            $data = Link::all()->groupBy('type');
        }else{
            $data = Link::all();
        }
        return $this->sendResponse($data, null);
    }

    public function detail(Request $req, $id)
    {
        try {
            $data = Link::findOrFail($id);
        } catch(ModelNotFoundException $e){
            return $this->sendErrorResponse(404, "NEWS_NOT_EXISTS", [], null);
        }
        return $this->sendResponse($data, null);
    }

    public function create(Request $req)
    {
        if(auth()->user()->level == 0)
            return $this->sendErrorResponse(403, "USER_PERMISSION_DENY", [], null);

        $inputs = [
            'url' => $req->url,
            'type' => $req->type,
            'name' => $req->name,
            'download' => $req->download,
        ];
        $rules = [
            'url' => 'required|string',
            'type' => 'required|string',
            'name' => 'required|string',
            'download' => 'required',
        ];
        $validator = Validator::make($inputs, $rules);
        $uniqueURL = Validator::make(['url' => $req->url], ['url' => 'unique:links']);
        if($validator->fails())
            $this->sendErrorResponse(403, "LINK_MISSING_FIELD", [], null);
        if($uniqueURL->fails())
            $this->sendErrorResponse(401, "LINK_ALREADY_EXISTS", [], null);

        $data = Link::create($inputs);
        return $this->sendResponse($data, null);
    }

    public function update(Request $req, $id)
    {
        if(auth()->user()->level == 0)
            return $this->sendErrorResponse(403, "USER_PERMISSION_DENY", [], null);

        try {
            $data = Link::findOrFail($id);
        } catch(ModelNotFoundException $e){
            return $this->sendErrorResponse(404, "HONOR_NOT_EXISTS", [], null);
        }

        if(!is_null($req->url)){
            $uniqueURL = Validator::make(['url' => $req->url], ['url' => 'unique:links']);
            if($uniqueURL->fails())
                $this->sendErrorResponse(401, "LINK_ALREADY_EXISTS", [], null);
        }
        $data->update($req->all());
        return $this->sendResponse($data, null);
    }

    public function delete(Request $req, $id)
    {
        if(auth()->user()->level == 0)
            return $this->sendErrorResponse(403, "USER_PERMISSION_DENY", [], null);

        try {
            $data = File::findOrFail($id);
        } catch(ModelNotFoundException $e){
            return $this->sendErrorResponse(404, "LINK_NOT_EXISTS", [], null);
        }

        $data = Link::destroy($id);
        return $this->sendResponse([], null);
    }
}
