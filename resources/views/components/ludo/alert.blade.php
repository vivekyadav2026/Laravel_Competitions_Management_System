@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-4 py-2 px-3 small border-0 shadow-sm" role="alert">
        <i class="fa-solid fa-circle-check me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close btn-close-white small p-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show rounded-4 py-2 px-3 small border-0 shadow-sm" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close btn-close-white small p-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger rounded-4 py-2 px-3 small border-0 shadow-sm mb-3">
        <div class="fw-bold mb-1"><i class="fa-solid fa-circle-exclamation me-1"></i> Please check the following:</div>
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
