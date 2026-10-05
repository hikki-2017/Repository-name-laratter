<x-layouts.app :title="__('コメント詳細')">
  <div class="p-6">
    <a href="{{ route('tweets.show', $tweet) }}" class="text-blue-500 hover:text-blue-700">Tweetに戻る</a>

    <p class="text-sm text-gray-500 mt-2">{{ $tweet->tweet }}: {{ $tweet->user->name }}</p>
    <p class="text-lg mt-2">{{ $comment->comment }}</p>
    <p class="text-sm text-gray-500">{{ $comment->user->name }}</p>
    <p class="text-sm text-gray-500">コメント作成日時: {{ $comment->created_at->format('Y-m-d H:i') }}</p>
    <p class="text-sm text-gray-500">コメント更新日時: {{ $comment->updated_at->format('Y-m-d H:i') }}</p>

    @if (auth()->id() == $comment->user_id)
    <div class="flex mt-4">
      <a href="{{ route('tweets.comments.edit', [$tweet, $comment]) }}" class="text-blue-500 hover:text-blue-700 mr-2">編集</a>
      <form action="{{ route('tweets.comments.destroy', [$tweet, $comment]) }}" method="POST" onsubmit="return confirm('本当に削除しますか？');">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-red-500 hover:text-red-700">削除</button>
      </form>
    </div>
    @endif
  </div>
</x-layouts.app>
