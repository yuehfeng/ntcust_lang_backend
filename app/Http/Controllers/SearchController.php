<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use App\Models\Album;
use App\Models\Banner;
use App\Models\Honor;
use App\Models\Link;
use App\Models\Member;
use App\Models\News;
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController as BaseController;

class SearchController extends BaseController
{
    public function index(Request $req)
    {
        $keyword = $req->keyword;
        $news = News::where('title', 'like', '%'.$keyword.'%')
                    ->orWhere('context', 'like', '%'.$keyword.'%')
                    ->whereNull('deleted_at')
                    ->get();
        $album = Album::where('name', 'like', '%'.$keyword.'%')
                    ->orWhere('context', 'like', '%'.$keyword.'%')
                    ->orWhere('description', 'like', '%'.$keyword.'%')
                    ->get();
        $banner = Banner::where('title', 'like', '%'.$keyword.'%')
                    ->orWhere('description', 'like', '%'.$keyword.'%')
                    ->get();
        $honor = Honor::where('title', 'like', '%'.$keyword.'%')
                    ->orWhere('content', 'like', '%'.$keyword.'%')
                    ->get();
        $link = Link::where('name', 'like', '%'.$keyword.'%')
                    ->get();
        $data = [
            "news" => [
                "data" => $news,
                "rows" => count($news)
            ],
            "album" => [
                "data" => $album,
                "rows" => count($album)
            ],
            "banner" => [
                "data" => $banner,
                "rows" => count($banner)
            ],
            "honor" => [
                "data" => $honor,
                "rows" => count($honor)
            ],
            "link" => [
                "data" => $link,
                "rows" => count($link)
            ]
        ];
        return $this->sendResponse($data, null);
    }
}
