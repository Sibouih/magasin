<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\utils\helpers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Intervention\Image\ImageManagerStatic as Image;

class CategorieController extends BaseController
{

    //-------------- Get All Categories ---------------\\

    public function index(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', Category::class);
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField;
        $dir = $request->SortType;
        $helpers = new helpers();

        $categories = Category::where('deleted_at', '=', null)

        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('name', 'LIKE', "%{$request->search}%")
                        ->orWhere('code', 'LIKE', "%{$request->search}%");
                });
            });
        $totalRows = $categories->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $categories = $categories->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        return response()->json([
            'categories' => $categories,
            'totalRows' => $totalRows,
        ]);
    }

    //-------------- Store New Category ---------------\\

    public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Category::class);
        
        request()->validate([
            'name' => 'required',
            'code' => 'required',
        ]);

        if ($request->hasFile('image')) {

            $image = $request->file('image');
            $filename = rand(11111111, 99999999) . $image->getClientOriginalName();

            $image_resize = Image::make($image->getRealPath());
            $image_resize->resize(200, 200);
            $image_resize->save(public_path('/images/categories/' . $filename));

        } else {
            $filename = 'no-image.png';
        }

        Category::create([
            'code' => $request['code'],
            'name' => $request['name'],
            'image' => $filename,
        ]);
        return response()->json(['success' => true]);
    }

     //------------ function show -----------\\

    public function show($id){
        //
    
    }

    //-------------- Update Category ---------------\\

    public function update(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'update', Category::class);
        request()->validate([
            'name' => 'required',
            'code' => 'required',
        ]);
    \DB::transaction(function () use ($request, $id) {
        $category = Category::findOrFail($id);
        $currentImage = $category->image;

        if ($currentImage && $request->image != $currentImage) {
            $image = $request->file('image');
            $path = public_path() . '/images/categories';
            $filename = rand(11111111, 99999999) . $image->getClientOriginalName();

            $image_resize = Image::make($image->getRealPath());
            $image_resize->resize(200, 200);
            $image_resize->save(public_path('/images/categories/' . $filename));

            $CategoryImage = $path . '/' . $currentImage;
            if (file_exists($CategoryImage)) {
                if ($currentImage != 'no-image.png') {
                    @unlink($CategoryImage);
                }
            }
        } else if (!$currentImage && $request->image !='null'){
            $image = $request->file('image');
            $path = public_path() . '/images/categories';
            $filename = rand(11111111, 99999999) . $image->getClientOriginalName();

            $image_resize = Image::make($image->getRealPath());
            $image_resize->resize(200, 200);
            $image_resize->save(public_path('/images/categories/' . $filename));
        }

        else {
            $filename = $currentImage?$currentImage:'no-image.png';
        }

        Category::whereId($id)->update([
            'code' => $request['code'],
            'name' => $request['name'],
            'image' => $filename,
        ]);

    }, 10);
        return response()->json(['success' => true]);

    }

    //-------------- Remove Category ---------------\\

    public function destroy(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'delete', Category::class);

        Category::whereId($id)->update([
            'deleted_at' => Carbon::now(),
        ]);
        return response()->json(['success' => true]);
    }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'delete', Category::class);
        $selectedIds = $request->selectedIds;

        foreach ($selectedIds as $category_id) {
            Category::whereId($category_id)->update([
                'deleted_at' => Carbon::now(),
            ]);
        }

        return response()->json(['success' => true]);
    }

}
