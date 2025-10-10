<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="Fever Code Challenge API Documentation",
 *      description="API documentation for the Fever Code Challenge solution."
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
