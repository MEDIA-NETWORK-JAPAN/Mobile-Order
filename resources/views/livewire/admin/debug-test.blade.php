<div>
    <div class="bg-white p-8 rounded-lg shadow">
        <h1 class="text-3xl font-bold text-red-600 mb-6">🔧 Livewire動作確認テスト</h1>
        
        <div class="mb-6">
            <p class="text-lg mb-2"><strong>メッセージ:</strong> {{ $message }}</p>
            <p class="text-lg mb-4"><strong>カウンター:</strong> <span class="text-2xl font-bold text-blue-600">{{ $counter }}</span></p>
        </div>

        <div class="space-y-4">
            <button 
                wire:click="increment" 
                class="bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-6 rounded-lg text-lg"
            >
                ➕ カウンターアップ
            </button>

            <button 
                wire:click="testAlert" 
                class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-6 rounded-lg text-lg ml-4"
            >
                🔔 アラートテスト
            </button>
        </div>

        <div class="mt-8 p-4 bg-gray-100 rounded">
            <h3 class="font-bold mb-2">確認項目：</h3>
            <ul class="list-disc list-inside space-y-1">
                <li>このページが表示されている → Livewire基本動作OK</li>
                <li>カウンターボタンクリックで数字が増える → wire:click動作OK</li>
                <li>ログにincrement()メッセージが出力される → サーバー通信OK</li>
            </ul>
        </div>
    </div>
</div>