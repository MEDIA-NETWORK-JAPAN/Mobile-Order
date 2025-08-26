<div>
    <h2 class="text-2xl font-bold mb-6">最小限の商品作成テスト</h2>
    
    <div class="bg-white p-6 rounded shadow">
        <p>カウンター: {{ $counter ?? 0 }}</p>
        
        <div class="mt-4">
            <button wire:click="testCounter" type="button" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded mr-2">
                カウンターテスト
            </button>
            
            <button wire:click="testMethod" type="button" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded mr-2">
                testMethodテスト
            </button>
            
            <button wire:click="save" type="button" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">
                saveテスト
            </button>
        </div>
    </div>
</div>