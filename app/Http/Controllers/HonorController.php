<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\BaseController as BaseController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Honor;
use App\Models\User;
use Carbon\Carbon;

class HonorController extends BaseController
{
    public function read(Request $req)
    {
        $data = Honor::all();
        return $this->sendResponse($data, null);
    }

    public function detail(Request $req, $id)
    {
        try {
            $data = Honor::findOrFail($id);
        } catch(ModelNotFoundException $e){
            return $this->sendErrorResponse(404, "HONOR_NOT_EXISTS", [], null);
        }
        return $this->sendResponse($data, null);
    }

    public function create(Request $req)
    {
        if(auth()->user()->level == 0)
            return $this->sendErrorResponse(403, "USER_PERMISSION_DENY", [], null);

        $inputs = [
            'title' => $req->title,
            'content' => $req->content,
            'ranking' => $req->ranking,
            'ranking_style' => $req->ranking_style,
            'department' => $req->department
        ];
        $rules = [
            'title' => 'required|string',
            'content' => 'required|string',
            'ranking' => 'required|string',
            'ranking_style' => 'required',
            'department' => 'required'
        ];
        $validator = Validator::make($inputs, $rules);
        if($validator->fails())
            $this->sendErrorResponse(403, "FILE_MISSING_FIELD", [], null);

        $data = Honor::create($inputs);
        return $this->sendResponse($data, null);
    }

    public function update(Request $req, $id)
    {
        if(auth()->user()->level == 0)
            return $this->sendErrorResponse(403, "USER_PERMISSION_DENY", [], null);

        try {
            $data = Honor::findOrFail($id);
        } catch(ModelNotFoundException $e){
            return $this->sendErrorResponse(404, "HONOR_NOT_EXISTS", [], null);
        }
        $data->update($req->all());
        return $this->sendResponse($data, null);
    }

    public function delete(Request $req, $id)
    {
        if(auth()->user()->level == 0)
            return $this->sendErrorResponse(403, "USER_PERMISSION_DENY", [], null);

        try {
            $data = Honor::findOrFail($id);
        } catch(ModelNotFoundException $e){
            return $this->sendErrorResponse(404, "HONOR_NOT_EXISTS", [], null);
        }

        $data = Honor::destroy($id);
        return $this->sendResponse([], null);
    }
}
