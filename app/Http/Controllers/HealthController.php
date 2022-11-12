<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HealthController extends Controller
{
    public function get(Request $request)
    {
        $request->validate([
            'resources' => 'string|required|max:255'
        ]);

        $resourcesNamesConcatenated = $request->input('resources');
        $resourceNames = explode('-', $resourcesNamesConcatenated);
        $validResources = app('pragmarx.health')->getResources();

        foreach ($resourceNames as $resourceName) {
            $resource = $validResources->where('abbreviation', $resourceName)->first();
            if (!isset($resource)) {
                return response()->json(['error' => $resourceName . ' is not a valid health check resource. '
                    . 'Please use the abbreviation from the resource configuration.'], 422);
            }
            $resource = app('pragmarx.health')->checkResource($resource);
            if (!$resource->isHealthy()) {
                $results = [];
                foreach ($resource->targets as $target) {
                    $results[] = [
                        'name' => $target->getName(),
                        'result' => $target->getResult()
                    ];
                }
                Log::alert($resource->name . ' health check failed: ' . json_encode($results));
                return response()->json([
                    'error' => $resource->name . ' health check failed',
                    'results' => $results
                ], 500);
            }
        }

        return response()->json();
    }
}
