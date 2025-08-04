<div class="flex gap-1 flex-wrap">
    @foreach ($images as $img)
        <img src="{{ asset('storage/' . $img->image_path) }}" class="w-10 h-10 rounded object-cover border" alt="صورة" />
    @endforeach
</div>
