<x-layouts.app :title="__('コメント作成')">
  <div class="p-6">
    <a href="{{ route('tweets.show', $tweet) }}" class="text-blue-500 hover:text-blue-700">Tweetに戻る</a>

    <form method="POST" action="{{ route('tweets.comments.store', $tweet) }}" class="mt-4">
      @csrf
      <div class="mb-4">
        <label for="comment" class="block text-sm font-bold mb-2">コメント</label>
        <input type="text" name="comment" id="comment" class="border rounded w-full py-2 px-3 dark:bg-gray-700">
        @error('comment')
        <span class="text-red-500 text-xs italic">{{ $message }}</span>
        @enderror
      </div>
      <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">コメントする</button>
    </form>
  </div>
</x-layouts.app>
