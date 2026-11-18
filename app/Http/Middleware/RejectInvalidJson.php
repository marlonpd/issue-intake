<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RejectInvalidJson
{
    public function handle(Request $request, Closure $next): Response
    {
        $content = $request->getContent();

        if ($request->isJson() && $content !== '' && ! json_validate($content)) {
            abort(400, 'The request body is not valid JSON.');
        }

        return $next($request);
    }
}
