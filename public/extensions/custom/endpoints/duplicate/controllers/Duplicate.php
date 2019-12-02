<?php

use Directus\Application\Http\Request;
use Directus\Application\Http\Response;


require_once __DIR__ . '/../functions.php';

class Duplicate
{
    public function __invoke(Request $request, Response $response)
    {
        return $response->withJson(['data' => start($request)]);
        
    }
        
}
