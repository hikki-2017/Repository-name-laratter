<x-layouts.app :title="__('Tweet作成')">
  <div class="p-6">
    <h2 class="font-semibold text-xl mb-4">{{ __('Tweet作成') }}</h2>

    <form method="POST" action="{{ route('tweets.store') }}">
      @csrf
      <div class="mb-4">
        <label for="tweet" class="block text-sm font-bold mb-2">Tweet</label>
        <input type="text" name="tweet" id="tweet" class="border rounded w-full py-2 px-3 dark:bg-gray-700">
        @error('tweet')
        <span class="text-red-500 text-xs italic">{{ $message }}</span>
        @enderror
      </div>
      <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Tweet</button>
    </form>
  </div>
</x-layouts.app>
