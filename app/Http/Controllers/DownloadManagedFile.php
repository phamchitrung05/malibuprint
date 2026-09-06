<?php

namespace App\Http\Controllers;

use App\Models\ManagedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadManagedFile extends Controller
{
    public function __invoke(ManagedFile $managedFile): StreamedResponse
    {
        // URL nội bộ chỉ phục vụ bản staging; khi upload xong web_view_link sẽ chuyển sang Google Drive.
        abort_unless($managedFile->temporary_disk && $managedFile->temporary_path, 404);

        $disk = Storage::disk($managedFile->temporary_disk);
        abort_unless($disk->exists($managedFile->temporary_path), 404);

        return $disk->download(
            $managedFile->temporary_path,
            $managedFile->original_name,
            array_filter(['Content-Type' => $managedFile->mime_type]),
        );
    }
}
