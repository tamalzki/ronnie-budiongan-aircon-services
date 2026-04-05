{{-- Session flash messages (success / error / warning) — consistent dismissible alerts --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show app-flash border-0 shadow-sm mb-2 py-2" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show app-flash border-0 shadow-sm mb-2 py-2" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show app-flash border-0 shadow-sm mb-2 py-2" role="alert">
        {{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
