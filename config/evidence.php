<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Evidence Upload Limits
    |--------------------------------------------------------------------------
    |
    | Limits that apply when attaching evidence (photos or videos) to an
    | affectation. Values are shared with the public registration wizard and
    | the affectation editor. Sizes are expressed in megabytes in the .env
    | file and converted to bytes here.
    |
    */

    'max_files' => (int) env('EVIDENCE_MAX_FILES', 5),

    'max_image_bytes' => (int) env('EVIDENCE_MAX_IMAGE_MB', 10) * 1024 * 1024,

    'max_video_bytes' => (int) env('EVIDENCE_MAX_VIDEO_MB', 100) * 1024 * 1024,

];
