<x-layouts.app :title="__('Tweet編集')">
  <div class="p-6">
    <a href="{{ route('tweets.show', $tweet) }}" class="text-blue-500 hover:text-blue-700">詳細に戻る</a>

    <form method="POST" action="{{ route('tweets.update', $tweet) }}" class="mt-4">
      @csrf
      @method('PUT')
      <div class="mb-4">
        <label for="tweet" class="block text-sm font-bold mb-2">Edit Tweet</label>
        <input type="text" name="tweet" id="tweet" value="{{ $tweet->tweet }}" class="border rounded w-full py-2 px-3 dark:bg-gray-700">
        @error('tweet')
        <span class="text-red-500 text-xs italic">{{ $message }}</span>
        @enderror
      </div>
      <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Update</button>
    </form>
  </div>
</x-layouts.app>
