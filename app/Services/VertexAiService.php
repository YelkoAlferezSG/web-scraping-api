<?php

namespace App\Services;

use Google\ApiCore\ApiException;
use Google\Cloud\AIPlatform\V1\Client\PredictionServiceClient;
use Google\Cloud\AIPlatform\V1\Content;
use Google\Cloud\AIPlatform\V1\DirectPredictRequest;
use Google\Cloud\AIPlatform\V1\GenerateContentRequest;
use Google\Cloud\AIPlatform\V1\GenerateContentResponse;
use Google\Cloud\AIPlatform\V1\Part;

class VertexAIService
{
    private $projectId;
    private $locationId;
    private $modelId;

    public function __construct()
    {
        $this->projectId = env('PROJECT_ID', 'innpark');
        $this->locationId = env('LOCATION_ID', 'us-central1');
        $this->modelId = env('MODEL_ID', 'gemini-2.0-flash-exp');
    }

    function generateTextFromVertexAI($model, $prompt)
    {
        // Create a client.
        $predictionServiceClient = new PredictionServiceClient();

        $contentsParts = new Part();
        $contentsParts->setText($prompt); 

        $content = (new Content())
            ->setParts([$contentsParts])
            ->setRole('user');
        $contents = [$content];
        $request = (new GenerateContentRequest())
            ->setModel($model)
            ->setContents($contents);

        try {
            $response = $predictionServiceClient->generateContent($request);
        
            return $response;
        } catch (ApiException $ex) {
            return $ex;
        }
    }


    // function generateTextFromVertexAI(string $formattedEndpoint, string $prompt): void
    // {
    //     // Crear un cliente
    //     $predictionServiceClient = new ClientPredictionServiceClient();

    //     // Configurar el prompt dentro de un objeto Protobuf Value
    //     $instance = new ProtoValue([
    //         'string_value' => $prompt
    //     ]);

    //     // Prepara la solicitud
    //     $request = (new PredictRequest())
    //         ->setEndpoint($formattedEndpoint)  // Endpoint completo del modelo
    //         ->setInstances([$instance]);      // Instancia con el prompt

    //     try {
    //         // Llamar a la API
    //         $response = $predictionServiceClient->predict($request);

    //         // Procesar y mostrar la respuesta
    //         foreach ($response->getPredictions() as $prediction) {
    //             printf('Response data: %s' . PHP_EOL, $prediction->serializeToJsonString());
    //         }
    //     } catch (ApiException $ex) {
    //         // Manejar errores
    //         printf('Call failed with message: %s' . PHP_EOL, $ex);
    //     }
    // }
}
