<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;

use App\Http\Requests\Dashboard\Service\StoreServiceRequest;
use App\Http\Requests\Dashboard\Service\UpdateServiceRequest;

use Illuminate\Support\facades\Storage;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// use File;
// use Alert;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;


use App\Models\Service;
use App\Models\AdvantageService;
use App\Models\AdvantageUser;
use App\Models\Tagline;
use App\Models\ThumbnailService;
use App\Models\Order;
use App\Models\User;

class ServiceController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

     public function __construct()
    {
        $this->middleware('auth');
    }

     public function index()
    {

        $services = Service::where('users_id', Auth::user()->id)->orderBy('created_at', 'desc')->get();
        return view('pages.dashboard.service.index', compact('services'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('pages.dashboard.service.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreServiceRequest $request)
    {

        $data = $request->all();

        $data['users_id'] = Auth::user()->id;
        // dd($data);
        // add to service
        $service = Service::create($data);

        // add to advantage service
        foreach($data['advantage-service'] as $key => $value){
            
            $advantage_service = new AdvantageService;
            $advantage_service->service_id = $service->id;
            $advantage_service->advantage = $value;
            $advantage_service->save();

        }

        // add to advantage user
        foreach($data['advantage-user'] as $key => $value){
            
            $advantage_user = new AdvantageUser;
            $advantage_user->service_id = $service->id;
            $advantage_user->advantage = $value;
            $advantage_user->save();

        }

        // add to thumbnail service
        // has file helper dari laravel untu mengecek request ada filenya atau tidak
        if ($request->hasFile('thumbnail')) 
        {
            // berapa banyak foto yg d masukan di looping
            foreach($request->file('thumbnail') as $file)
            {
                // masukan file kedalam store dan path di tuju
                $path = $file->store(
                    'asset/service/thumbnail', 'public'
                );

                $advantage_service = new ThumbnailService();
                // bisa $service->id atau $service['id']
                $advantage_service->service_id = $service['id'];
                $advantage_service->thumbnail = $path;
                $advantage_service->save();
            }
        }


        // add to tagline
        foreach($data['tagline'] as $key => $value){
    
            $tagline = new Tagline;
            $tagline->service_id = $service->id;
            $tagline->tagline = $value;
            $tagline->save();

        }

        toast()->success('Save has been success');

        return redirect()->route('member.service.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    // Service yang dipanggil modelnya
     public function edit(Service $service)
    {
        // bisa service['id'] atau service->id
        $advantage_service = AdvantageService::where('service_id', $service->id)->get();
        $tagline = Tagline::where('service_id',$service['id'])->get();
        $advantage_user = AdvantageUser::where('service_id', $service['id'])->get();
        $thumbnail_service = ThumbnailService::where('service_id', $service['id'])->get();
            

        return view('pages.dashboard.service.edit', compact('service','advantage_service','tagline','advantage_user','thumbnail_service'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateServiceRequest $request, Service $service)
    {
        $data = $request->all();
        // dd($data);
        // update to service
        $service->update($data);

        // update to advantage service
        if(isset($data['advantage-services'])){
            foreach ($data['advantage-services'] as $key => $value) {
                $advantage_service = new AdvantageService;
                $advantage_service->advantage = $value;
                $advantage_service->save();
            }
        }

        // add new advantage service
        if(isset($data['advantage_service'])){
            foreach ($data['advantage_service'] as $key => $value) {
                $advantage_service = new AdvantageService;
                $advantage_service->service_id = $service['id'];
                $advantage_service->advantage = $value;
                $advantage_service->save();
            }
        }

        // update to advantage user
        if(isset($data['advantage-users'])){
            foreach ($data['advantage-users'] as $key => $value) {
                $advantage_user = AdvantageUser::find($key);
                $advantage_user->advantage = $value;
                $advantage_user->save();
            }
        }

        // add new advantage user
        if(isset($data['advantage_user'])){
            foreach ($data['advantage_user'] as $key => $value) {
                $advantage_user = New AdvantageUser;
                $advantage_user->service_id = $service['id'];
                $advantage_user->advantage = $value;
                $advantage_user->save();
            }
        }
        
        // update to tagline
        if(isset($data['taglines'])){
            foreach ($data['taglines'] as $key => $value) {
                $tagline = Tagline::find($key);
                $tagline->tagline = $value;
                $tagline->save();
            }
        }   

        // add new tagline
        if(isset($data['tagline'])){
            foreach ($data['tagline'] as $key => $value) {
                $tagline = new Tagline;
                $tagline->service_id = $service['id'];
                $tagline->tagline = $value;
                $tagline->save();
            }
        }

        // update to thumbnail service
        if ($request->hasFile('thumbnails')) {
            foreach ($request->file('thumbnails') as $key => $file) {
                
                // get old photo thumbnail
                $get_photo = ThumbnailService::where('id', $key)->first();

                // store photo

                $path = $file->store(
                    'asset/service/thumbnail', 'public'
                );

                // update thumbnail
                $thumbnail_service = ThumbnailService::find($key);
                $thumbnail_service->thumbnail = $path;
                $thumbnail_service->save();

                // delete old photo thumbnail
                $data = 'storage'.$get_photo['photo'];
                if (File::exists($data)) {
                    File::delete($data);
                }else{
                    File::delete('storage/app/public'.$get_photo['photo']);
                }



            }
        }

        //  add new thumbnail service
        if ($request->hasFile('thumbnail')) {
            foreach ($request->file('thumbnails') as $file) {
                
                // get old photo thumbnail
                // $get_photo = ThumbnailService::where('id', $key)->first();

                // store photo

                $path = $file->store(
                    'asset/service/thumbnail', 'public'
                );

                // update thumbnail
                $thumbnail_service = ThumbnailService::find($key);
                $thumbnail_service->thumbnail = $path;
                $thumbnail_service->save();

            }
        }

        toast()->success('Update has been success');

        return redirect()->route('member.service.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        return abort(404);
    }
}
