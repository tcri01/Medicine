<div class="container">
    <h2>我的檔案</h2>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('profile.files.upload') }}" method="POST" enctype="multipart/form-data"
        class="mb-4 p-4 bg-white rounded shadow flex items-center gap-4">
        @csrf
        <label for="file" class="font-semibold text-gray-700 mr-2">選擇圖片：</label>
        <input type="file" name="file" id="file" accept="image/*"
            class="form-control-file border rounded px-2 py-1" required>
        <button type="submit"
            class="btn btn-primary bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">上傳圖片</button>
    </form>

    <hr>

    <div class="row">
        @forelse($files as $file)
            <div class="col-md-3 mb-3">
                <img src="{{ $file->url() }}" class="img-fluid" alt="user file">
            </div>
        @empty
            <p>尚未上傳任何圖片。</p>
        @endforelse
    </div>
</div>
