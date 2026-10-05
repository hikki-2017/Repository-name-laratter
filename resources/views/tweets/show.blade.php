<x-layouts.app :title="__('Tweet詳細')">
  <div class="p-6">
    <a href="{{ route('tweets.index') }}" class="text-blue-500 hover:text-blue-700">一覧に戻る</a>

    <p class="text-lg mt-2">{{ $tweet->tweet }}</p>
    <p class="text-sm text-gray-500">投稿者: {{ $tweet->user->name }}</p>
    <p class="text-sm text-gray-500">作成日時: {{ $tweet->created_at->format('Y-m-d H:i') }}</p>
    <p class="text-sm text-gray-500">更新日時: {{ $tweet->updated_at->format('Y-m-d H:i') }}</p>

    @if (auth()->id() == $tweet->user_id)
    <div class="flex mt-4">
      <a href="{{ route('tweets.edit', $tweet) }}" class="text-blue-500 hover:text-blue-700 mr-2">編集</a>
      <form action="{{ route('tweets.destroy', $tweet) }}" method="POST" onsubmit="return confirm('本当に削除しますか？');">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-red-500 hover:text-red-700">削除</button>
      </form>
    </div>
    @endif

    <div class="flex mt-4">
      @if ($tweet->liked->contains(auth()->id()))
      <form action="{{ route('tweets.dislike', $tweet) }}" method="POST">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-red-500 hover:text-red-700">dislike {{ $tweet->liked->count() }}</button>
      </form>
      @else
      <form action="{{ route('tweets.like', $tweet) }}" method="POST">
        @csrf
        <button type="submit" class="text-blue-500 hover:text-blue-700">like {{ $tweet->liked->count() }}</button>
      </form>
      @endif
    </div>

    <div class="mt-4">
      <p class="text-sm text-gray-500">comment {{ $tweet->comments->count() }}</p>
      <a href="{{ route('tweets.comments.create', $tweet) }}" class="text-blue-500 hover:text-blue-700">コメントする</a>
    </div>

    <div class="mt-4">
      @foreach ($tweet->comments as $comment)
      <a href="{{ route('tweets.comments.show', [$tweet, $comment]) }}">
        <p>{{ $comment->comment }} <span class="text-sm text-gray-500">{{ $comment->user->name }} {{ $comment->created_at->format('Y-m-d H:i') }}</span></p>
      </a>
      @endforeach
    </div>
  </div>
</x-layouts.app>
