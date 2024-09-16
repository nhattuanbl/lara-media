<div class="app-aside flex-column z-index-1">
    <div class="app-navbar flex-stack flex-shrink-0 p-5 pt-lg-12 pb-lg-8 px-lg-14">
        <div class="d-flex align-items-center me-5 me-lg-10">
            <div class="app-navbar-item me-4">
                <div class="cursor-pointer symbol symbol-40px h-40px w-40px bg-warning d-flex justify-content-center align-items-center">
                    {{--                                        <img src="https://ui-avatars.com/api/?name=John+Doe" alt="user" />--}}
                    <i class="ki-duotone ki-user fs-1 text-white">
                        <i class="path1"></i>
                        <i class="path2"></i>
                    </i>
                </div>
            </div>
            <div class="d-flex flex-column">
                <a href="#" class="app-navbar-user-name text-gray-900 text-hover-primary fs-5 fw-bold">{{ $username }}</a>
                <span class="app-navbar-user-info text-gray-600 fw-semibold fs-7">Media management</span>
            </div>
        </div>
        <div class="app-navbar-item">
            <div class="btn btn-icon btn-custom btn-dark w-40px h-40px app-navbar-user-btn">
                <i class="ki-outline ki-notification-on fs-1"></i>
            </div>
        </div>
    </div>
    <div class="app-aside_content-wrapper hover-scroll-overlay-y mx-3 ps-5 ps-lg-11 pe-3 pe-lg-11 pb-10">
        <div class="card card-p-0 card-reset bg-transparent mb-7 mb-lg-10" wire:poll.3s>
            <div class="card-header border-0 pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold text-gray-900">Recent activities
                        <span class="badge badge-light-secondary">{{ $countTotal }}</span>
                    </span>
                    <span class="text-muted mt-1 fw-semibold fs-7">
                        <span class="badge badge-light-primary">Processing: {{ $countProcessing }}</span>
                        <span class="badge badge-light-success">Pending: {{ $countPending }}</span>
                        <span class="badge badge-light-danger">Failed: {{ $countFailed }}</span>
                    </span>
                </h3>
                <div class="card-toolbar">
{{--                    <a href="#" class="btn btn-sm btn-light">Pending: 2</a>--}}
                </div>
            </div>
            <div class="card-body pt-6">
                @foreach($jobList as $media)
                    @php
                    /** @var \Nhattuanbl\LaraMedia\Models\LaraMedia $media */
                    if ($media->is_video) {
                        $bg = 'bg-danger';
                        $icon = 'bi bi-film fs-1';
                    } else if ($media->is_image) {
                        $bg = 'bg-info';
                        $icon = 'ki-outline ki-picture';
                    } else if ($media->is_audio) {
                        $bg = 'bg-primary';
                        $icon = 'bi bi-file-earmark-music-fill';
                    } else {
                        $bg = 'bg-success';
                        $icon = 'ki-outline ki-file';
                    }
                    @endphp
                    <div class="d-flex flex-stack">
                        <div class="symbol symbol-40px me-4">
                            <div class="symbol-label fs-2 fw-semibold {{ $bg }}">
                                <i class="{{ $icon }} fs-1 text-white"></i>
                            </div>
                        </div>
                        <div class="d-flex align-items-center flex-row-fluid flex-wrap">
                            <div class="flex-grow-1 me-2">
                                <a href="#" class="text-gray-800 text-hover-primary fs-6 fw-bold searchForMediaID" data-id="{{ $media->_id }}">{{ $media->properties['name'] }}.{{ $media->ext }}</a>
                                <span class="text-muted fw-semibold d-block fs-7">
                                    @if($media->status->is_finished || $media->status->is_failed || $media->status->is_queued || $media->status->is_retrying)
                                        {{ mb_ucfirst($media->status->status) }} -
                                        {{ $media->status->is_finished ? ' ' . $media->status->finished_at->format('M d, g:i A') : '' }}
                                        {{ $media->status->is_failed ? ' ' . $media->status->updated_at->format('M d, g:i A') : '' }}
                                        {{ $media->status->is_queued ? ' ' . $media->status->created_at->format('M d, g:i A') : '' }}
                                        {{ $media->status->is_retrying ? ' ' . $media->status->updated_at->format('M d, g:i A') : '' }}
                                    @elseif (!$media->status->status)
                                        Queued - {{ $media->status->created_at->format('M d, g:i A') }}
                                    @else
                                        {{ $media->status->output['message'] ?? 'Error unable to get status !!!' }}
                                    @endif
                                </span>
                                @if($media->status->is_executing)
                                <div class="progress mt-1" style="height: 5px">
                                    <div class="progress-bar {{ $bg }} progress-bar-striped progress-bar-animated" role="progressbar"
                                         style="width: {{ $media->status->progress_now }}%" aria-valuenow="{{ $media->status->progress_now }}" aria-valuemin="0" aria-valuemax="{{ $media->status->progress_max }}"></div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="separator separator-dashed my-4"></div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@script
<script>
    window.mimeTypeIsVideo = function(mimeType) {
        return mimeType.startsWith('video/');
    }

    window.mimeTypeIsAudio = function(mimeType) {
        return mimeType.startsWith('audio/');
    }

    window.mimeTypeIsImage = function(mimeType) {
        return mimeType.startsWith('image/');
    }

    $('.app-aside').on('click', '.searchForMediaID', function(e) {
        e.preventDefault();
        $('#media-search').val($(this).data('id')).keyup();
    });
</script>
@endscript
