<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Session;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Controllers\BaseController as BaseController;
use Illuminate\Support\Facades\Validator;
use App\Models\News;
use App\Models\View;
use App\Models\User;
use App\Models\Notification;
use Carbon\Carbon;

class NewsController extends BaseController
{
    public function read(Request $req)
    {
        $page = is_null($req->page)?1:$req->page;
        $orderby = is_null($req->orderby)?'asc':$req->orderby;
        $fields = is_null($req->fields)?'created_at':$req->fields;
        $parameter = [
            'fields' => $fields,
            'order' => $orderby
        ];
        $nums = ceil(count(News::whereNull('deleted_at')->get()) / 10);

        $items = News::whereNull('deleted_at')
            ->orderBy('top', 'desc')
            ->orderBy($fields, $orderby)
            ->skip(($page-1) * 10)
            ->take(10)
            ->get();

        $results = [];
        foreach($items as $key)
        {
            $results[] = [
                "id" => $key->id,
                "title" => $key->title,
                "context" => $key->context,
                "created_at" => $key->created_at,
                "updated_at" => $key->updated_at,
                "editor" => User::find($key->editor)->name,
                "notification" => Notification::find($key->type),
                "views" => View::where([['type_id', 2], ['sheet_id', $key->id]])->count(),
                "top" => (bool)($key->top)
            ];
        }

        $data = [
            "result" => $results,
            "pages" => [
                "all" => $nums,
                "current" => (int)$page,
            ]
        ];

        $parameter = [
            "page" => (int)$page,
            "orderby" => $orderby,
            "fields" => $fields
        ];
        return $this->sendResponse($data, $parameter);
    }

    public function all(Request $req)
    {
        $page = is_null($req->page)?1:$req->page;
        $orderby = is_null($req->orderby)?'asc':$req->orderby;
        $fields = is_null($req->fields)?'created_at':$req->fields;
        $parameter = [
            'fields' => $fields,
            'order' => $orderby
        ];

        $nums = ceil(count(News::all()) / 10);
        $items = News::orderBy('top', 'desc')
            ->orderBy($fields, $orderby)
            ->skip(($page-1) * 10)
            ->take(10)
            ->get();

        $results = [];
        foreach($items as $key)
        {
            $results[] = [
                "id" => $key->id,
                "title" => $key->title,
                "context" => $key->context,
                "created_at" => $key->created_at,
                "updated_at" => $key->updated_at,
                "deleted_at" => $key->deleted_at,
                "editor" => User::find($key->editor)->name,
                "notification" => Notification::find($key->type),
                "views" => View::where([['type_id', 2], ['sheet_id', $key->id]])->count(),
                "top" => (bool)($key->top)
            ];
        }

        $data = [
            "result" => $results,
            "pages" => [
                "all" => $nums,
                "current" => (int)$page,
            ]
        ];

        $parameter = [
            "page" => (int)$page,
            "orderby" => $orderby,
            "fields" => $fields
        ];
        return $this->sendResponse($data, $parameter);
    }

    public function detail(Request $req)
    {
        try {
            $row = News::findOrFail($req->id);
        } catch(ModelNotFoundException $e){
            return $this->sendErrorResponse(404, "NEWS_NOT_EXISTS", [], null);
        }

        // 新增一筆觀看紀錄
        $user = View::create([
            'type_id' => 2,
            'sheet_id' => $req->id,
            'located' => $req->ip()
        ]);

        $data = [
            "id" => $row->id,
            "title" => $row->title,
            "context" => $row->context,
            "created_at" => $row->created_at,
            "updated_at" => $row->updated_at,
            "editor" => User::find($row->editor)->name,
            "notification" => Notification::find($row->type),
            "views" => View::where([['type_id', '2'], ['sheet_id', $row->id]])->count()
        ];
        return $this->sendResponse($data, null);
    }

    public function create(Request $req)
    {
        if(auth()->user()->level == 0)
            return $this->sendErrorResponse(403, "USER_PERMISSION_DENY", [], null);
        $inputs = [
            'title' => $req->title,
            'context' => $req->context,
            'type' => $req->type
        ];
        $rules = [
            'title' => 'required|string',
            'context' => 'required|string',
            'type' => 'numeric'
        ];
        $validator = Validator::make($inputs, $rules);
        if($validator->fails())
            return $this->sendErrorResponse(400, "NEWS_MISSING_FIELD", $validator->messages(), null);

        $news = News::create([
            'title' => $req->title,
            'context' => $req->context,
            'type' => $req->type,
            'editor' => auth()->user()->id
        ]);

        return $this->sendResponse($news, null);
    }

    public function update(Request $req, $id)
    {
        if(auth()->user()->level == 0)
            return $this->sendErrorResponse(403, "USER_PERMISSION_DENY", [], null);

        try {
            $news = News::findOrFail($id);
        }catch(ModelNotFoundException $e){
            return $this->sendErrorResponse(404, "NEWS_NOT_EXISTS", [], null);
        }
        
        $news->update($req->all());
        $news->updated_at = Carbon::now()->toDateTimeString();
        $news->editor = auth()->user()->id;
        $news->save();
        return $this->sendResponse($news, null);
    }

    public function top(Request $req, $id)
    {
        if(auth()->user()->level == 0)
            return $this->sendErrorResponse(403, "USER_PERMISSION_DENY", [], null);
        try {
            $news = News::findOrFail($id);
        }catch(ModelNotFoundException $e){
            return $this->sendErrorResponse(404, "NEWS_NOT_EXISTS", null, null);
        }

        $news->top = ($news->top)?false:true;
        $news->updated_at = Carbon::now()->toDateTimeString();
        $news->save();
        return $this->sendResponse($news, null);
    }

    public function softdelete(Request $req, $id)
    {
        if(auth()->user()->level == 0)
            return $this->sendErrorResponse(403, "USER_PERMISSION_DENY", [], null);

        try {
            $news = News::findOrFail($id);
        }catch(ModelNotFoundException $e){
            return $this->sendErrorResponse(404, "NEWS_NOT_EXISTS", [], null);
        }
        
        if(is_null($news->deleted_at))
        {
            $news->deleted_at = Carbon::now()->toDateTimeString();
        }else{
            $news->deleted_at = null;
        }
        $news->save();
        return $this->sendResponse($news, null);
    }

    public function delete(Request $req, $id)
    {
        if(auth()->user()->level == 0)
            return $this->sendErrorResponse(403, "USER_PERMISSION_DENY", [], null);

        try {
            $news = News::findOrFail($req->id);
        } catch(ModelNotFoundException $e){
            return $this->sendErrorResponse(404, "NEWS_NOT_EXISTS", [], null);
        }

        $news = News::destroy($id);
        return $this->sendResponse([], null);
    }
}
