<?php

return [
    'ffmpeg_binary' => env('FFMPEG_BINARY', 'ffmpeg'),
    'ffprobe_binary' => env('FFPROBE_BINARY', 'ffprobe'),
    'ffmpeg_timeout' => (int) env('FFMPEG_TIMEOUT', 600),
];
