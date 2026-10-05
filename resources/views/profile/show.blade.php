<x-layouts.app :title="__('User詳細')">
  <div class="p-6">
    <h2 class="font-semibold text-xl mb-4">{{ __('User詳細') }}</h2>
    <a href="{{ route('tweets.index') }}" class="text-blue-500 hover:text-blue-700 mr-2">一覧に戻る</a>
    <p class="text-lg mt-2">{{ $user->name }}</p>
    <div class="text-sm text-gray-500">
      <p>アカウント作成日時: {{ $user->created_at->format('Y-m-d H:i') }}</p>
    </div>

    @if ($user->id !== auth()->id())
    <div class="mt-2">
      @if ($user->followers->contains(auth()->id()))
      <form action="{{ route('follow.destroy', $user) }}" method="POST">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-red-500 hover:text-red-700">unFollow</button>
      </form>
      @else
      <form action="{{ route('follow.store', $user) }}" method="POST">
        @csrf
        <button type="submit" class="text-blue-500 hover:text-blue-700">follow</button>
      </form>
      @endif
    </div>
    @endif

    <p class="mt-2">following: {{ $user->follows->count() }}</p>
    <p>followers: {{ $user->followers->count() }}</p>

    @if ($tweets->count())

    <!-- ページネーション -->
    <div class="my-4">
      {{ $tweets->appends(request()->input())->links() }}
    </div>

    @foreach ($tweets as $tweet)
    <div class="mb-4 p-4 bg-gray-100 dark:bg-gray-700 rounded-lg">
      <p>{{ $tweet->tweet }}</p>
      <a href="{{ route('profile.show', $tweet->user) }}">
        <p class="text-sm text-gray-500">投稿者: {{ $tweet->user->name }}</p>
      </a>
      <a href="{{ route('tweets.show', $tweet) }}" class="text-blue-500 hover:text-blue-700">詳細を見る</a>
      <div class="flex mt-2">
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
    </div>
    @endforeach

    <!-- ページネーション -->
    <div class="mt-4">
      {{ $tweets->appends(request()->input())->links() }}
    </div>

    @else
    <p class="mt-4">No tweets found.</p>
    @endif
  </div>
</x-layouts.app>
