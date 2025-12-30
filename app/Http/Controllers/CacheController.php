<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class CacheController extends Controller
{
    /**
     * キャッシュをクリアする
     */
    public function clearCache()
    {
        try {
            // Laravelの各種キャッシュをクリア
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            Artisan::call('cache:clear');
            
            return response()->json([
                'success' => true,
                'message' => 'キャッシュをクリアしました'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'キャッシュクリアに失敗しました: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vueのアセットを再ビルドする
     */
    public function buildVue()
    {
        try {
            $basePath = base_path();
            $output = [];
            $returnCode = 0;
            
            // npm run dev を実行
            $command = "cd {$basePath} && npm run dev 2>&1";
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Vueアセットのビルドが完了しました',
                    'output' => implode("\n", $output)
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Vueアセットのビルドに失敗しました',
                    'output' => implode("\n", $output)
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ビルド実行中にエラーが発生しました: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * キャッシュクリアとVueビルドを両方実行
     */
    public function clearAndBuild()
    {
        try {
            // まずキャッシュをクリア
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            Artisan::call('cache:clear');
            
            // 次にVueをビルド
            $basePath = base_path();
            $output = [];
            $returnCode = 0;
            
            $command = "cd {$basePath} && npm run dev 2>&1";
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'キャッシュクリアとVueアセットのビルドが完了しました',
                    'output' => implode("\n", $output)
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Vueアセットのビルドに失敗しました',
                    'output' => implode("\n", $output)
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '処理中にエラーが発生しました: ' . $e->getMessage()
            ], 500);
        }
    }
}

