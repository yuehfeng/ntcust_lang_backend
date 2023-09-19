<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Album;
use App\Models\User;
use App\Models\View;
use App\Models\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Controllers\BaseController as BaseController;
use Carbon\Carbon;

class AlbumController extends BaseController
{
    public function read(Request $req)
    {
        $data = [];
        if($req->all == true){
            $result = Album::all();
        }else{
            $result = Album::whereNotNull('cover_file')->get();
        }

        foreach($result as $key)
        {
            $data[] = [
                "id" => $key->id,
                "name" => $key->name,
                "context" => $key->context,
                "description" => $key->description,
                "created_at" => $key->created_at,
                "updated_at" => $key->updated_at,
                "views" => View::where([['type_id', '5'], ['sheet_id', $key->id]])->count(),
                "cover" => File::find($key->cover_file)
            ];
        }
        return $this->sendResponse($data, null);
    }

    public function detail(Request $req, $id)
    {
        try {
            $result = Album::findOrFail($id);
        }catch(ModelNotFoundException $e){
            return $this->sendErrorResponse(404, "MSG_DATA_NOT_FOUND", [], null);
        }

        View::create([
            'type_id' => 5,
            'sheet_id' => $id,
            'located' => $req->ip()
        ]);

        $data = [
            "id" => $result->id,
            "name" => $result->name,
            "context" => $result->context,
            "description" => $result->description,
            "created_at" => $result->created_at,
            "updated_at" => $result->updated_at,
            "editor" => User::find($result->editor),
            "views" => View::where([['type_id', '5'], ['sheet_id', $req->id]])->count(),
            "cover" => File::find($result->cover_file)
        ];
        return $this->sendResponse($data, null);
    }

    public function create(Request $req)
    {
        if(auth()->user()->level == 0)
            return $this->sendErrorResponse(403, "USER_PERMISSION_DENY", [], null);

        $inputs = [
            'context' => $req->context,
            'description' => $req->description,
            'name' => $req->name
        ];
        $rules = [
            'context' => 'required|string',
            'description' => 'required|string',
            'name' => 'required|string'
        ];
        $validator = Validator::make($inputs, $rules);
        if($validator->fails())
            return $this->sendErrorResponse(400, $validator->messages(), [], null);
        // ---
        $validator = Validator::make($inputs, $rules);
        $rules = [ 'name' => 'unique:albums' ];
        if($validator->fails())
            return $this->sendErrorResponse(400, "ALBUM_NAME_DUPLICATES", [], null);

        $data = Album::create([
            'name' => $req->name,
            'description' => $req->description,
            'context' => $req->context
        ]);
        return $this->sendResponse($data, null);
    }

    public function update(Request $req)
    {
        if(auth()->user()->level == 0)
            return $this->sendErrorResponse(403, "USER_PERMISSION_DENY", [], null);
        try {
            $data = Album::findOrFail($req->id);
        } catch(ModelNotFoundException $e){
            return $this->sendErrorResponse(404, "MSG_DATA_NOT_FOUND", [], null);
        }
        $data->update($req->all());
        $data->updated_at = Carbon::now()->toDateTimeString();
        $data->save();

        return $this->sendResponse([], null);
    }

    public function delete(Request $req, $id)
    {
        try {
            $data = Album::findOrFail($id);
        }catch(ModelNotFoundException $e){
            return $this->sendErrorResponse(404, "MSG_DATA_NOT_FOUND", null, null);
        }
        
        $user = Album::destroy($id);
        return $this->sendResponse([], null);
    }
}
