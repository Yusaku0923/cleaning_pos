@extends('layouts.app')

@section('content')
<div class="col-12">
    <div class="col-12 d-flex justify-content-around mx-auto mb-3">
        <a href="{{ route('daily_report.index') }}" class="card col-5 fs-26 p-4 cbtn cbtn-blue text-center">日報</a>
        <a href="{{ route('invoice.index') }}" class="card col-5 fs-26 p-4 cbtn cbtn-blue text-center">請求書</a>
    </div>
    <div class="col-12 d-flex justify-content-around mx-auto mb-3">
        <a class="card col-5 fs-26 p-4 cbtn cbtn-blue text-center">担当者マスタ</a>
        <a href="{{ route('clothes.create') }}" class="card col-5 fs-26 p-4 cbtn cbtn-blue text-center">商品マスタ</a>
    </div>
    <div class="col-12 d-flex justify-content-around mx-auto mb-3">
        <a href="{{ route('delivery.index') }}" class="card col-5 fs-26 p-4 cbtn cbtn-green text-center">納品書管理</a>
        <a href="{{ route('receipt.edit') }}" class="card col-5 fs-26 p-4 cbtn cbtn-blue text-center">レシート設定</a>
    </div>
    <div class="col-12 d-flex justify-content-around mx-auto mb-3">
        <button id="browser-cache-clear-btn" class="card col-5 fs-26 p-4 cbtn cbtn-yellow text-center" style="border: none; cursor: pointer;">
            ブラウザキャッシュクリア
        </button>
    </div>
    @if(config('app.env') === 'local')
    <div class="col-12 d-flex justify-content-around mx-auto mb-3">
        <button id="clear-cache-btn" class="card col-5 fs-26 p-4 cbtn cbtn-yellow text-center" style="border: none; cursor: pointer;">
            キャッシュクリア
        </button>
        <button id="build-vue-btn" class="card col-5 fs-26 p-4 cbtn cbtn-green text-center" style="border: none; cursor: pointer;">
            Vue再ビルド
        </button>
    </div>
    <div class="col-12 d-flex justify-content-around mx-auto mb-3">
        <button id="clear-and-build-btn" class="card col-12 fs-26 p-4 cbtn cbtn-orange text-center" style="border: none; cursor: pointer;">
            キャッシュクリア + Vue再ビルド
        </button>
    </div>
    <div id="cache-result" class="col-12 mt-3" style="display: none;">
        <div class="alert alert-info" role="alert">
            <div id="cache-message"></div>
            <pre id="cache-output" style="max-height: 300px; overflow-y: auto; margin-top: 10px; font-size: 12px;"></pre>
        </div>
    </div>
    @endif
</div>

@if(config('app.env') === 'local')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    function showResult(success, message, output = '') {
        const resultDiv = document.getElementById('cache-result');
        const messageDiv = document.getElementById('cache-message');
        const outputDiv = document.getElementById('cache-output');
        
        resultDiv.style.display = 'block';
        messageDiv.textContent = message;
        messageDiv.className = success ? 'text-success' : 'text-danger';
        outputDiv.textContent = output;
        
        // 3秒後に自動で非表示
        setTimeout(() => {
            resultDiv.style.display = 'none';
        }, 10000);
    }
    
    function executeAction(url, actionName) {
        const btn = event.target;
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = '実行中...';
        
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btn.textContent = originalText;
            showResult(data.success, data.message, data.output || '');
        })
        .catch(error => {
            btn.disabled = false;
            btn.textContent = originalText;
            showResult(false, 'エラーが発生しました: ' + error.message);
        });
    }
    
    document.getElementById('clear-cache-btn')?.addEventListener('click', function() {
        executeAction('{{ route("cache.clear") }}', 'キャッシュクリア');
    });
    
    document.getElementById('build-vue-btn')?.addEventListener('click', function() {
        executeAction('{{ route("cache.build-vue") }}', 'Vue再ビルド');
    });
    
    document.getElementById('clear-and-build-btn')?.addEventListener('click', function() {
        executeAction('{{ route("cache.clear-and-build") }}', 'キャッシュクリア + Vue再ビルド');
    });
});
</script>
@endif

{{-- ブラウザキャッシュクリアボタン（本番環境でも表示） --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const browserCacheBtn = document.getElementById('browser-cache-clear-btn');
    if (browserCacheBtn) {
        browserCacheBtn.addEventListener('click', function() {
            // localStorageとsessionStorageをクリア
            try {
                localStorage.clear();
                sessionStorage.clear();
            } catch (e) {
                console.log('Storage clear error:', e);
            }
            
            // Service Workerのキャッシュをクリア（もし使用している場合）
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.getRegistrations().then(function(registrations) {
                    for(let registration of registrations) {
                        registration.unregister();
                    }
                });
            }
            
            // キャッシュバスティング付きでページをリロード
            const timestamp = new Date().getTime();
            window.location.href = window.location.pathname + '?v=' + timestamp;
        });
    }
});
</script>
@endsection
