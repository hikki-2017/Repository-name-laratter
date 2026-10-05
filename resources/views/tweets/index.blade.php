<x-layouts.app :title="__('Tweet一覧')">
  <div class="p-6">
    <h2 class="font-semibold text-xl mb-4">{{ __('Tweet一覧') }}</h2>

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
  </div>
</x-layouts.app>
