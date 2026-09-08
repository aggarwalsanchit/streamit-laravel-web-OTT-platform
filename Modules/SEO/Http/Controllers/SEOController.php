<?php

namespace Modules\SEO\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\SEO\Models\Seo;
use Modules\SEO\Http\Requests\SeoRequest;
use App\Traits\ModuleTrait;
use Illuminate\Support\Facades\Auth;

class SEOController extends Controller
{
    /**
     * Display the SEO settings form.
     */

     use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
    }


     public function __construct()
     {
         $this->traitInitializeModuleTrait(
             'settings.title', // module title
             'settings', // module name
             'fa-solid fa-clipboard-list' // module icon
         );
     }

public function index()
{
    $seo = Seo::first(); // Get the first SEO record
    $page_type = 'seo';
    if(!$seo){
         $seoData = [
            'title' => $seo->meta_title ?? 'Default Title for SEO',
            'description' => $seo->short_description ?? 'Default Description',
            'keywords' => [], // Ensure this is passed as an array
            'author' => $seo->author ?? 'Default Author',
            'seo_image' => !empty($seo->seo_image) ? setBaseUrlWithFileName($seo->seo_image, 'image', 'seo') : '',
        ];

        return view('seo::index', compact('seo', 'seoData', 'page_type'));
    }

    // Ensure 'meta_keywords' is an array or a comma-separated string
    $keywords = $seo->meta_keywords ? explode(',', $seo->meta_keywords) : [];

    $seoData = [
        'title' => $seo->meta_title ?? 'Default Title for SEO',
        'description' => $seo->short_description ?? 'Default Description',
        'keywords' => $keywords, // Ensure this is passed as an array
        'author' => $seo->author ?? 'Default Author',
        'seo_image' => !empty($seo->seo_image) ? setBaseUrlWithFileName($seo->seo_image, 'image', 'seo') : '',
    ];

    return view('seo::index', compact('seo', 'seoData', 'page_type'));
}

public function store(SeoRequest $request)
{
    // Restrict SEO updates for demo admin when demo login is enabled
    $user = Auth::user();
    $isDemoAdmin = $user && ($user->hasRole('demo_admin') || $user->user_type === 'demo_admin');
    if (setting('demo_login', 0) == 1 && $isDemoAdmin) {
        return response()->json([
            'success' => false,
            'message' => __('messages.user_does_not_have_permission_to_change_password'),
        ], 403);
    }

    $requestData = $request->all();

    // Validate unique meta_title except when updating the same record
    $id = $request->input('id');
    if (Seo::where('meta_title', $requestData['meta_title'])
           ->when($id, fn($q) => $q->where('id', '!=', $id))
           ->exists()) {

        return redirect()->back()
            ->withErrors(['meta_title' => 'This Meta Title is already taken. Please choose a different one.']);
    }

    // Ensure meta_keywords is stored as comma-separated string
    $requestData['meta_keywords'] = isset($requestData['meta_keywords']) && is_array($requestData['meta_keywords'])
        ? implode(',', $requestData['meta_keywords'])
        : '';

    if (!empty($requestData['seo_image'])) {
        $requestData['seo_image'] = extractFileNameFromUrl($requestData['seo_image'], 'seo');

    }

    // Create or update in one line
    $seo = Seo::updateOrCreate(
        ['id' => $id],  // match by ID
        $requestData
    );

    // Return JSON for AJAX requests
    if ($request->ajax() || $request->wantsJson()) {
        // Set full URL for seo_image in response
        $responseData = $seo->toArray();
        if (!empty($responseData['seo_image'])) {
            $responseData['seo_image'] = setBaseUrlWithFileName($responseData['seo_image'], 'image', 'seo');
        }

        return response()->json([
            'success' => true,
            'message' => $id ? __('messages.seo_update') : __('messages.seo_save'),
            'data' => $responseData
        ], 200);
    }

    return redirect()->back()->with('success', 'SEO settings saved successfully!');
}





public function update(SeoRequest $request, int $id)    
{
    // Validate the incoming request
    $data = $request->validated();


    // Handle image - extract filename from URL (from media library) or handle file upload
    if (!empty($data['seo_image'])) {
        if ($request->hasFile('seo_image')) {
            // Direct file upload
            $image = validateUploadedFile($request->file('seo_image'), 'image');
            $safeName = sanitizeUploadedFilename($image, 'image');
            $path = $image->storeAs('public/uploads/seo', $safeName);
            $data['seo_image'] = basename($path);
        } else {
            // URL from media library - extract filename using helper
            $data['seo_image'] = extractFileNameFromUrl($data['seo_image'], 'seo');
        }
    }

    // Find the SEO record by ID and update it with the new data
    $seo = Seo::findOrFail($id);
    $seo->update($data);


    // Redirect back with a success message
    return redirect()->back()->with('success', 'SEO settings updated successfully!');
}


}