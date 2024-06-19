<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use Spatie\RouteAttributes\Attributes\Get;

class UpController
{
    #[Get('/up', name: 'up')]
    public function __invoke(): mixed
    {
        return response()->json(['status' => 'ok']);
    }
}
