<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class YouTubeService
{
    public function getThumbnailByID(string $youtubeID)
    {
        $response = Http::asJson()
            ->baseUrl('https://youtube.googleapis.com/youtube/v3/')
            ->get('videos', [
                'part' => 'snippet',
                'id' => $youtubeID,
                'key' => config('services.youtube.key'),
            ])->collect('items');

        return $response[0]['snippet']['thumbnails']['default']['url'];
    }
}
