<?php

namespace App\Http\Controllers;
use App\Models\Image;
use App\Models\Service;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class ServiceController extends Controller
{

    public function __construct()
    {
        // $this->middleware('auth');
        $this->middleware('can:create-service')->only(['create', 'store']);
        $this->middleware('can:update-service')->only(['edit', 'update']);
        $this->middleware('can:delete-service')->only(['destroy']);
    }
    public function index()
    {
        $services = Service::with('images','admin.user')->latest()->paginate(10);
        return view('services.index', compact('services'));
    }

    public function create()
    {
        return view('services.create');
    }

    public function store(StoreServiceRequest $request)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required',
            'price' => 'required',
            'url.*' => 'required|image', // Accept an array of images
        ]);
    
        $service = Service::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'admin_id' => Admin::where('user_id', Auth::user()->id)->first()->id,
        ]);
    
        $destinationPath = public_path('images/serviceImages');
    
        // Loop through the images
        $images = $request->file('url') ;
        foreach ($images as $image) {

            $image_name = time() . '_' . $image->getClientOriginalName();
            $image->move($destinationPath, $image_name);

            Image::create([
                'url'=>$image_name,
                'service_id'=>$service->id
            ]);
        }

        return redirect()->route('services.index')->with('success', 'Service created successfully');
    }

    public function show(Service $service)
    {
        $service=Service::with('images')->find($service->id);
        return view('services.show', compact('service'));
    }

    public function edit(Service $service)
    {
        return view('services.edit', compact('service'));
    }

    public function update(UpdateServiceRequest $request, Service $service)
    {
        $service->update([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
        ]);
      
    
        $newImageModel = null;
    
        if ($request->hasFile('url')) {
            // delete old images multiple
            $oldImageModel = $service->images()->get();
            foreach ($oldImageModel as $image) {
                $imageUrl = public_path('images/serviceImages/'.$image->url);
                if (file_exists($imageUrl)) {
                    unlink($imageUrl);
                }
                $image->delete();
            }

            // upload new image
            $destinationPath = public_path('images/serviceImages');
    
            // Loop through the images
            $images = $request->file('url') ;
            foreach ($images as $image) {
    
                $image_name = time() . '_' . $image->getClientOriginalName();
                $image->move($destinationPath, $image_name);
    
                Image::create([
                    'url'=>$image_name,
                    'service_id'=>$service->id
                ]);
            }
        }
    
        $service->save();
        return redirect()->route('services.index')->with('success', 'Service updated successfully');
    }

    public function destroy(Service $service)
    {
            // delete image 
         
                $imageModel = $service->images()->first();

                if($imageModel == null){
                    $service->delete();
                }
                else{
                    $imageUrl = public_path('images/serviceImages/'.$imageModel->url);
                    if (file_exists($imageUrl)) {
                        unlink($imageUrl);
                    }
                    $imageModel->delete();
                    $service->delete();
                }

        return redirect()->route('services.index')->with('success', 'Service deleted successfully');


    }
}
