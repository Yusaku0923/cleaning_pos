@extends('layouts.app')

@section('content')
@php
    // [表示名, route名(nullなら準備中), 色]
    $menuGroups = [
        '日々の業務' => [
            ['日報', 'daily_report.index', 'blue'],
            ['請求書', 'invoice.index', 'blue'],
            ['納品書管理', 'delivery.index', 'green'],
        ],
        'マスタ・設定' => [
            ['担当者マスタ', null, 'blue'],
            ['商品マスタ', 'clothes.create', 'blue'],
            ['レシート設定', 'receipt.edit', 'blue'],
        ],
    ];
@endphp
<div class="col-12 menu-page px-3">
    @foreach ($menuGroups as $groupName => $items)
    <section class="menu-group mb-4">
        <h2 class="menu-group-title">{{ $groupName }}</h2>
        <div class="menu-grid">
            @foreach ($items as [$label, $routeName, $color])
                @if ($routeName)
                    <a href="{{ route($routeName) }}" class="menu-tile cbtn cbtn-{{ $color }}">{{ $label }}</a>
                @else
                    <span class="menu-tile menu-tile-disabled" aria-disabled="true">{{ $label }}<small>準備中</small></span>
                @endif
            @endforeach
        </div>
    </section>
    @endforeach

    <section class="menu-group mb-4">
        <h2 class="menu-group-title">メンテナンス</h2>
        <div class="menu-grid">
            <button id="browser-cache-clear-btn" type="button" class="menu-tile cbtn cbtn-yellow">
                ブラウザキャッシュクリア
                <small>画面の表示がおかしいときに実行</small>
            </button>
        </div>
    </section>
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
