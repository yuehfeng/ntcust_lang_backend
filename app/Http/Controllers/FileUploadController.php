<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Controllers\BaseController as BaseController;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\File;
use App\Models\FileLink;
use App\Models\User;

class FileUploadController extends BaseController
{
    public function read(Request $req, $id)
    {
        try {
            $item = File::findOrFail($id);
        } catch(ModelNotFoundException $e){
            return $this->sendErrorResponse(404, "MEDIA_NOT_EXISTS", [], null);
        }
        $data = [
            "file" => $item,
            "link" => FileLink::where('file_id', $id)->get()
        ];
        return $this->sendResponse($data, null);
    }

    public function readFile(Request $req, $id)
    {
        try {
            $item = File::findOrFail($id);
        } catch(ModelNotFoundException $e){
            return $this->sendErrorResponse(404, "MEDIA_NOT_EXISTS", [], null);
        }
        $data = [
            "file" => $item,
            "link" => FileLink::where('file_id', $id)->get()
        ];
        return $this->sendResponse($data, null);
    }

    public function filter($type_id, $sheet_id)
    {
        $inputs = [
            'sheet_id' => $sheet_id,
            'type_id' => $type_id
        ];
        $rules = [
            'sheet_id' => 'required',
            'type_id' => 'required',
        ];
        $validator = Validator::make($inputs, $rules);

        $data = FileLink::where([
            ['type_id', $type_id],
            ['sheet_id', $sheet_id]
        ])->get();

        $results = [];
        foreach($data as $key) {
            $results[] = [
                "id" => $key->file_id,
                "file" => File::find($key->file_id)
            ];
        }
        return $this->sendResponse($results, null);
    }

    public function create(Request $req)
    {
        if(auth()->user()->level == 0)
            return $this->sendErrorResponse(403, "USER_PERMISSION_DENY", [], null);
        
        $field_validator = Validator::make(
            ['file' => $req->file ],
            ['file' => 'required']
        );

        if($field_validator->fails())
            return $this->sendErrorResponse(403, "FILE_NOT_UPLOADED", [], null);
            
        $name = $req->file('file')->getClientOriginalName();
        $path = $req->file('file')->store('public/files');
        $data = File::create([
            'name' => $name,
            'path' => $path,
            'editor' => auth()->user()->id
        ]);
        return $this->sendResponse($data, null);
    }

    public function update(Request $req, $id)
    {
        if(auth()->user()->level == 0)
            return $this->sendErrorResponse(403, "USER_PERMISSION_DENY", [], null);
        try {
            $data = File::findOrFail($id);
        } catch(ModelNotFoundException $e){
            return $this->sendErrorResponse(404, "MEDIA_NOT_EXISTS", [], null);
        }

        $field_validator = Validator::make(
            ['file' => $req->file ],
            ['file' => 'required']
        );
        $file_max_validator = Validator::make(
            ['file' => $req->file],
            ['file' => 'max:10240']
        );

        if($field_validator->fails())
            $this->sendErrorResponse(403, "FILE_MISSING_FIELD", [], null);
        if($file_max_validator->fails())
            $this->sendErrorResponse(431, "FILE_REQUEST_HEADER_FIELDS_TOO_LARGE", [], null);

        $name = $req->file('file')->getClientOriginalName();
        $path = $req->file('file')->store('public/files');
        $data->name = $name;
        $data->path = $path;
        $data->editor = auth()->user()->id;
        $data->save();
        return $this->sendResponse($data, null);
    }

    public function delete(Request $req, $id)
    {
        if(auth()->user()->level == 0)
            return $this->sendErrorResponse(403, "USER_PERMISSION_DENY", [], null);

        try {
            $data = File::findOrFail($id);
        } catch(ModelNotFoundException $e){
            return $this->sendErrorResponse(404, "MEDIA_NOT_EXISTS", [], null);
        }

        File::destroy($id);
        FileLink::where('file_id', $id)->delete();
        return $this->sendResponse([], null);
    }

    public function createLink(Request $req)
    {
        try {
            $data = File::findOrFail($req->file_id);
        } catch(ModelNotFoundException $e){
            return $this->sendErrorResponse(404, "FILES_NOT_EXISTS", [], null);
        }

        $field_validator = Validator::make([
            'type_id' => $req->type_id,
            'sheet_id' => $req->sheet_id,
            'file_id' => $req->file_id
        ],[
            'type_id' => 'required',
            'sheet_id' => 'required',
            'file_id' => 'required'
        ]);

        if($field_validator->fails())
            $this->sendErrorResponse(403, "FILE_MISSING_FIELD", [], null);

        $data = FileLink::where([
            ['type_id', $req->type_id],
            ['sheet_id', $req->sheet_id],
            ['file_id', $req->file_id],
        ])->get();

        if(count($data) == 0){
            $data = FileLink::create([
                'type_id' => (int)$req->type_id,
                'sheet_id' => (int)$req->sheet_id,
                'file_id' => (int)$req->file_id
            ]);
        }else{
            return $this->sendErrorResponse(401, "FILE_ALREADY_EXISTS", [], null);
        }
        
        return $this->sendResponse($data, null);
    }

    public function updateLink(Request $req, $id)
    {
        $data = FileLink::where('file_id', $id)->get();
        if(count($data) == 0)
            return $this->sendErrorResponse(404, "FILES_NOT_EXISTS", [], null);
        
        $data = $data[0];
        if(!is_null($req->sheet_id)) $data->sheet_id = (int)$req->sheet_id;
        if(!is_null($req->type_id)) $data->sheet_id = (int)$req->type_id;
        $data->save();

        return $this->sendResponse($data, null);
    }
    
    public function deleteLink(Request $req, $id)
    {
        FileLink::where('file_id', $id)->delete();
        return $this->sendResponse([], null);
    }
}