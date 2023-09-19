<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Banner;
use App\Models\User;
use App\Models\File;
use App\Models\FileLink;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Controllers\BaseController as BaseController;
use Carbon\Carbon;

class BannerController extends BaseController
{
    public function read()
    {
        $data = [];
        $result = Banner::all();
        foreach($result as $key)
        {
            $data[] = [
                "id" => $key->id,
                "title" => $key->title,
                "description" => $key->description,
                "file" => File::find($key->file_id),
                "news_id" => $key->news_id,
                "editor" => User::find($key->editor)->value('name'),
                "updated_at" => $key->updated_at,
                "created_at" => $key->created_at
            ];
        }
        return $this->sendResponse($data, null);
    }

    public function create(Request $req)
    {
        if(auth()->user()->level == 0)
            return $this->sendErrorResponse(403, "USER_PERMISSION_DENY", [], null);

        $field_validator = Validator::make([
            'title' => $req->title,
            'description' => $req->description,
            'file_id' => $req->file_id
        ],[
            'title' => 'required',
            'description' => 'required',
            'file_id' => 'required'
        ]);
        $file_type_validator = Validator::make(
            ['file_id' => $req->file_id],
            ['file_id' => 'required|mimes:jpeg,jpg,png,gif']
        );
        $file_max_validator = Validator::make(
            ['file_id' => $req->file_id],
            ['file_id' => 'max:10240']
        );

        if($field_validator->fails())
            $this->sendErrorResponse(403, "ALBUMS_MISSING_FIELD", [], null);
        if($file_type_validator->fails())
            $this->sendErrorResponse(415, "FILE_UNSUPPORTED_MEDIA_TYPE", [], null);
        if($file_max_validator->fails())
            $this->sendErrorResponse(431, "FILE_REQUEST_HEADER_FIELDS_TOO_LARGE", [], null);

        // 檔案上傳(注意:上傳圖片的欄位就叫file_id)
        $name = $req->file('file_id')->getClientOriginalName();
        $path = $req->file('file_id')->store('public/files');

        $id = File::insertGetId([
            'name' => $name,
            'path' => $path,
            'editor' => auth()->user()->id
        ]);

        $link_file_id = FileLink::insertGetId([
            'type_id' => 4,
            'sheet_id' => 0,
            'file_id' => $id
        ]);

        $banner = Banner::create([
            'title' => $req->title,
            'description' => $req->description,
            'file_id' => $id,
            'news_id' => $req->news_id,
            'editor' => auth()->user()->id
        ]);

        $row = FileLink::find($link_file_id);
        $row->sheet_id = $banner->id;
        $row->save();

        return $this->sendResponse($banner, null);
    }

    public function update(Request $req, $id)
    {
        if(auth()->user()->level == 0)
            return $this->sendErrorResponse(403, "USER_PERMISSION_DENY", [], null);

        try {
            $banner = Banner::findOrFail($id);
        } catch(ModelNotFoundException $e){
            $this->sendErrorResponse(404, "MSG_DATA_NOT_FOUND", [], null);
        }

        $field_validator = Validator::make([
            'title' => $req->title,
            'description' => $req->description,
        ],[
            'title' => 'required',
            'description' => 'required',
        ]);
        if($field_validator->fails())
            $this->sendErrorResponse(403, "ALBUMS_MISSING_FIELD", [], null);

        $banner->title = $req->title;
        $banner->description = $req->description;
        $banner->updated_at = Carbon::now()->toDateTimeString();
        $banner->save();
        return $this->sendResponse($banner, null);
    }

    public function delete(Request $req, $id)
    {
        if(auth()->user()->level == 0)
            return $this->sendErrorResponse(403, "USER_PERMISSION_DENY", [], null);

        try {
            $data = Banner::findOrFail($id);
        } catch(ModelNotFoundException $e){
            $this->sendErrorResponse(404, "MSG_DATA_NOT_FOUND", [], null);
        }
        Banner::destroy($id);
        FileLink::where('file_id', $id)->delete();
        return $this->sendResponse($data, null);
    }
}
