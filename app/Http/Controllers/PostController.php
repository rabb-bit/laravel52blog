<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Post;
use App\Category;
use App\Tag;
use Illuminate\Support\Facades\Storage;
use Mews\Purifier\Facades\Purifier;
use Image;
use Auth;
class PostController extends Controller {
    public function __construct() {
        $this->middleware('auth');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $posts = Post::orderBy('created_at', 'desc')->paginate(10);

        return view('posts.index')->withPosts($posts);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        $categories = Category::all();
        $tags = Tag::all();

        return view('posts.create')->withCategories($categories)->withTags($tags);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        //dd($request);
        $this->validate($request, [
            'title'          => 'required|max:255',
            'category_id'    => 'required|integer',
            'body'           => 'required',
            'featured_image' => 'sometimes'
        ]);
        $post = new Post;
        $post->title = $request->title;
        $post->category_id = $request->category_id;
        $post->body = Purifier::clean($request->body);

        if ($request->featured_image) {
            $image = pathinfo($request->featured_image);
            $filename = $image['filename'] . '.' . $image['extension'];
            $post->image = $filename;
        }
        $post->save();
        $post->tags()->sync($request->tags, false);
        flash()->overlay('You have successfully added a post', 'Success');

        return redirect()->route('posts.show', $post->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        $post = Post::find($id);
        $user = Auth::id();

        return view('posts.show')->withPost($post)->withUser($user);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        $post = Post::find($id);
        $user = Auth::id();
        $categories = Category::all();
        $cats = array();
        foreach ($categories as $category) {
            $cats[$category->id] = $category->name;
        }
        $tags = Tag::all();
        $tags2 = array();
        foreach ($tags as $tag) {
            $tags2[$tag->id] = $tag->name;
        }

        return view('posts.edit')->withPost($post)->withCategories($cats)->withTags($tags2)->withUser($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        //dd($request);
        $this->validate($request, [
            'title'          => 'required|max:255',
            'body'           => 'required',
            'category_id'    => 'required|integer',
            'featured_image' => 'sometimes|max:255'
        ]);
        $post = Post::find($id);
        $post->title = $request->input('title');
        $post->body = Purifier::clean($request->input('body'));
        $post->category_id = $request->category_id;
        if ($request->featured_image) {
            $image = pathinfo($request->featured_image);
            $filename = $image['filename'] . '.' . $image['extension'];
            $post->image = $filename;
        }
        $post->save();
        if (isset($request->tags)) {
            $post->tags()->sync($request->tags);
        } else {
            $post->tags()->sync(array());
        }
        flash()->overlay('You have successfully updated a post', 'Success');

        return redirect()->route('posts.show', $post->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $post = Post::find($id);
        $post->tags()->detach();
        $post->delete();
        flash()->overlay('You have successfully deleted a post', 'Delete');

        return redirect()->route('posts.index');
    }
}
