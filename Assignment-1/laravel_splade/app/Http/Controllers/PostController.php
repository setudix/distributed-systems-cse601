<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use App\Models\Post;
use App\Models\User;
use App\Notifications\NewPostNotification;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\Splade\Facades\Toast;
use ProtoneMedia\Splade\FormBuilder\File;
use ProtoneMedia\Splade\FormBuilder\Submit;
use ProtoneMedia\Splade\FormBuilder\Textarea;
use ProtoneMedia\Splade\SpladeForm;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $form = SpladeForm::make()
            ->id('post_form')
            ->class('space-y-4')
            ->fields([
                Textarea::make('content')->label('Write Your Post')->rules('required')->autosize(),
                File::make('images[]')
                    ->filepond()
                    ->multiple()
                    ->preview(),
                Submit::make()->label('Post'),
            ])
            ->method('post')
            ->action(route('dashboard.store'));

        $posts = Post::orderBy('created_at', 'desc')->get();

        return view('dashboard', [
            'form' => $form,
            'posts' => $posts,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // $image = $request->file('image');
        // $imagePath = Storage::disk('minio')->put('photos/photo.jpg', $image);

        // dd($imagePath, $request, $image);

        $request->validate([
            'content' => 'required',
            'images.*' => 'file|image|nullable',
        ]);

        $post = new Post();
        $post->content = $request->input('content');
        $post->user_id = Auth()->user()->id;
        $post->save();

        $pictures = $request->file('images');

        foreach ($pictures as $picture)
        {
            $path = Storage::disk('minio')->put('photos', $picture);

            $photo = new Photo();
            $photo->post_id = $post->id;
            $photo->path = $path;
            $photo->save();

        }

        $usersToNotify = User::where('id', '!=', auth()->id())->get();
        foreach ($usersToNotify as $user) 
        {
            $user->notify(new NewPostNotification($post));
        }

        Toast::title('Success!')
            ->message('Your post has been successfully stored!')
            ->success()
            ->autoDismiss(5);

        return redirect()->back();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $post = Post::findOrFail($id);

        $notification = Auth()->user()->notifications()
            ->where('data->post_id', $id)
            ->first();

        $notification->markAsRead();

        Toast::title('The Notification is marked as read');

        return view('post', [
            'post' => $post,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}