<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tweet extends Model
{
    use HasFactory;

    protected $fillable = ['tweet'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function liked()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    // 1対多の関係
    public function comments()
    {
        return $this->hasMany(Comment::class)->orderBy('created_at', 'desc');
    }

    // 検索用スコープ
    public function scopeKeyword(Builder $query, ?string $keyword): Builder
    {
        // キーワードが指定されている場合のみ絞り込む
        return $query->when($keyword, function (Builder $query, string $keyword) {
            // 部分一致（キーワードがどこかに含まれる）
            $query->where('tweet', 'like', '%'.$keyword.'%');
        });
    }

    // タイムライン用スコープ（自分とフォローしているユーザの Tweet）
    public function scopeTimeline(Builder $query, User $user): Builder
    {
        return $query
            ->where('user_id', $user->id)
            ->orWhereIn('user_id', $user->follows->pluck('id'));
    }
}
