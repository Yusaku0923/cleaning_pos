<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Mixed Content（HTTPリクエスト）を許可
        $response->headers->set('Content-Security-Policy', "default-src *; script-src * 'unsafe-inline'; style-src * 'unsafe-inline'; img-src * data: http: https:; connect-src * http: https: ws: wss:");

        return $response;
    }
}
