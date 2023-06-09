<?php

namespace App\Http\Livewire;

use App\Jobs\CreateImage;
use App\Models\Product;
use Livewire\Component;
use App\Models\Main_category;
use Carbon\Carbon;
use Faker\Core\File;
use Illuminate\Http\Testing\File as TestingFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File as FacadesFile;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use Intervention\Image\File as ImageFile;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Spatie\Backtrace\File as BacktraceFile;
use App\Jobs\GoogleVisionSafeSearch;
use App\Jobs\GoogleVisionLabelImage;
use App\Jobs\RemoveFaces;
use App\Jobs\Watermark;
use Spatie\Image\Manipulations;
use Spatie\Image\Image;

class CreateProduct extends Component
{
    use WithFileUploads;
    
    public $title;
    public $description;
    public $category;
    public $price;
    public $message;
    public $mainCategories;
    public $validated;
    public $temporary_images;
    public $images = [];
    public $image;
    public $products; 
    public $product; 

    
    protected $rules = [
        'title'=>'required|min:4',
        'description'=>'required:20',
        'price'=>'required|numeric',
        'mainCategories'=>'required',
        'images.*' =>  'image|max:1024',
        'temporary_images.*' =>  'image|max:1024',
    ];

    protected $messages = [
        'required'=>'Il campo :attribute è richiesto',
        'min'=>'Il campo :attribute è troppo corto',
        'numeric'=> 'Il campo :attribute deve essere un numero',
        'temporary_images.require' => 'L\' immagine pè richiesta',
        'temporary_images.*.image' => 'I file devono essere immagini',
        'temporary_images.*.max' => 'L\' immagine dev\' essere massimo di 1mb',
        'images.image' => 'L\' immagine dev\' essere un immagine',
        'images.image.require' => 'L\' immagine dev\' essere massimo di 1mb',

    ];

    public function updatedTemporaryImages(){
        if($this->validate([
            'temporary_images.*' =>  'image|max:1024',
        ])){
            foreach($this->temporary_images as $image){
                $this->images[] = $image;
            }
        }
    }

    public function removeImage($key){
        if (in_array($key, array_keys($this->images))){
            unset($this->images[$key]);
        }
    }

    public function cleanForm() {
        $this->title = '';
        $this->description = '';
        $this->price = '';
        $this->images = [];
        $this->temporary_images = [];
    }

    public function store() {
        $product = Product::create([
            'title'=>$this->title,
            'description'=>$this->description,
            'price'=>$this->price,
            'category'=>$this->category,
            'state'=>'pending',
            'user_id'=>Auth::user()->id
            
        ]);
        
        if (count($this->images)){
            foreach($this->images as $key => $image){
                
                
                $newImage = $product->images()->create(['path' => $image->storeAs('images/' . Auth::id(), '/crop_300x200_' .Str::slug($product['title'], '_'). $key . '.' . $image->extension(), 'public')]);
                
                dispatch(new CreateImage($newImage->path, 300, 200));
                dispatch(new GoogleVisionSafeSearch($newImage->id));
                dispatch(new GoogleVisionLabelImage($newImage->id));
                dispatch(new RemoveFaces ($newImage->id));
                dispatch(new Watermark($newImage->id));
                
                Storage::deleteDirectory(storage_path('app/livewire-tmp '));
            }
        }

        $product->categories()->attach($this->category);
        $product->save();
        session()->flash('success', 'Annuncio inserito correttamente');
        $this->cleanForm();

    }

    public function updated($propertyName) {
        $this->validateOnly($propertyName);
    }

    public function render()
    {
        return view('livewire.create-product');
    }
}
