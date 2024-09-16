<?php

return [
    public_path(env('LARA_MEDIA_PUBLIC_URL', '/assets/media')) => storage_path(env('LARA_MEDIA_PUBLIC_ROOT', 'app')),
];
