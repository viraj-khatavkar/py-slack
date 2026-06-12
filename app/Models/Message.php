<?php

namespace App\Models;

use App\Support\SlackContent;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    /** @use HasFactory<\Database\Factories\MessageFactory> */
    use HasFactory;

    protected $appends = ['content_html'];

    protected function casts(): array
    {
        return [
            'reactions' => 'array',
            'reply_users' => 'array',
            'is_edited' => 'boolean',
            'has_files' => 'boolean',
            'is_pinned' => 'boolean',
        ];
    }

    /** @return Attribute<string, never> */
    protected function contentHtml(): Attribute
    {
        return Attribute::get(fn (): string => SlackContent::render($this->content ?? ''));
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Channel, $this> */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /** @return BelongsTo<self, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<self, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
