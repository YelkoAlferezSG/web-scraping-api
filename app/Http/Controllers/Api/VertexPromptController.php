<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use Google_Client;
use Google_Service_Language;
use App\Http\Controllers\Controller;
use App\Services\VertexAIService;
use Exception;

class VertexPromptController extends Controller
{
    public function generate(Request $request)
    {
        try {
            $vertexAIService = new VertexAIService();

            $projectId = env('VERTEX_AI_PROJECT_ID', 'innpark');
            $locationId = env('VERTEX_AI_LOCATION_ID', 'us-central1');
            $modelId = env('VERTEX_AI_MODEL_ID', 'gemini-2.0-flash-exp');

            $formattedEndpoint = sprintf(
                "projects/%s/locations/%s/publishers/google/models/%s",
                $projectId,
                $locationId,
                $modelId
            );

            $prompt = $request->prompt;

            $response = $vertexAIService->generateTextFromVertexAI($formattedEndpoint, $prompt);

            return response()->json([
                'response' => $response
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    
}
