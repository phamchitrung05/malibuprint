<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attachment extends Model
{
    // Model này không chứa nội dung file; nó chỉ quản lý mối liên kết polymorphic và thứ tự hiển thị.
    protected $fillable = [
        'managed_file_id',
        'collection',
        'sort_order',
        'attached_by',
    ];

    public function managedFile(): BelongsTo
    {
        return $this->belongsTo(ManagedFile::class);
    }

    public function attachable(): MorphTo
    {
        // attachable hiện là Order và có thể mở rộng qua config/attachments.php trong tương lai.
        return $this->morphTo();
    }

    public function attachedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attached_by');
    }
}
