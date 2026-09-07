<?php

namespace App\Models;

use App\Support\StatusApp;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ManagedFile extends Model
{
    protected $fillable = [
        'uuid',
        'disk',
        'drive_file_id',
        'original_name',
        'storage_name',
        'path',
        'mime_type',
        'extension',
        'size',
        'checksum',
        'status',
        'temporary_disk',
        'temporary_path',
        'web_view_link',
        'web_content_link',
        'error_message',
        'uploaded_by',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'uploaded_at' => 'datetime',
        ];
    }

    public function attachments(): HasMany
    {
        // Một file có thể được tái sử dụng bởi nhiều Order hoặc model khác nhau.
        return $this->hasMany(Attachment::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeReady(Builder $query): Builder
    {
        // Thư viện chỉ cho chọn lại những file đã được xác minh tồn tại trên storage đích.
        return $query->where('status', StatusApp::value('managed_file.status', 'ready'));
    }
}
