<?php

namespace App\Models\Concerns;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

trait HasAttachments
{
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->latest('id');
    }

    public function attach(UploadedFile $file, ?User $uploader = null): Attachment
    {
        $disk = config('attachments.disk');
        $folder = sprintf('attachments/%s/%s', Str::of(class_basename($this))->snake(), $this->getKey());
        $name = Str::uuid().'-'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            .'.'.$file->getClientOriginalExtension();

        $path = $file->storeAs($folder, $name, $disk);

        /** @var Attachment $attachment */
        $attachment = $this->attachments()->create([
            'user_id' => $uploader?->id,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        if (method_exists($this, 'audit')) {
            $this->audit('attachment_added', null, ['name' => $attachment->original_name]);
        }

        return $attachment;
    }
}
