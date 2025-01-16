<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use Google_Client;
use Google_Service_Language;
use App\Http\Controllers\Controller;

class WebScrapingController extends Controller
{
    public function scrape(Request $request)
    {
        $url = $request->query('url');

        // Verificar que la URL es válida
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return response()->json([
                'error' => 'URL no válida.'
            ], 400);
        }

        // Crear el cliente Guzzle
        $client = new Client();

        try {
            // Realizar la solicitud GET
            $response = $client->get($url);
            $html = (string) $response->getBody(); // Obtener el contenido HTML

            // Usar DomCrawler para analizar el HTML
            $crawler = new Crawler($html);

            // Eliminar los elementos de tipo script, style, noscript, etc.
            $crawler->filter('script, style, noscript')->each(function (Crawler $node) {
                $node->getNode(0)->parentNode->removeChild($node->getNode(0)); // Remover los elementos
            });

            // Extraer el título del producto desde el primer <h1>
            $title = $crawler->filter('h1')->text();

            // Extraer solo el texto significativo de párrafos y otros elementos de texto (como h2, h3, p, etc.)
            $bodyText = $crawler->filter('body')->each(function (Crawler $node) {
                return $node->text();
            });

            // Unir todos los textos obtenidos en una sola cadena
            $bodyText = implode(' ', $bodyText);

            // Llamamos a la función que extrae las keywords usando Google Cloud Natural Language
            $keywords = $this->extractKeywordsFromBody($title, $bodyText);

            // Retornar los resultados como respuesta JSON
            return response()->json([
                'title' => $title,
                'keywords' => $keywords
            ]);
        } catch (\Exception $e) {
            // Manejar errores si la solicitud falla
            return response()->json([
                'error' => 'Error al obtener la página: ' . $e->getMessage()
            ], 500);
        }
    }

    private function extractKeywordsFromBody($title, $bodyText)
    {
        
        $languageServiceClient = new LanguageServiceClient();

        // Prepara el documento
        $document = new Document([
            'content' => 'Fabricamos cinta fruncidora automática blanca para barras con presilla de hilos. Cinta fruncidora pasabarra muy práctica. Disponible en armadora y fruncidora automática. Cinta de cortina blanca o beige para cortina de barras de telas finas o transparentes, cortinas de decoración hogar, habitaciones infantiles, etc.',
            'type' => Document\Type::PLAIN_TEXT
        ]);
        
        // Crea una instancia de Features y configura las propiedades
        $features = new Features();
        $features->setExtractEntities(true);  // Habilita la extracción de entidades
        $features->setExtractDocumentSentiment(true);  // Habilita el análisis de sentimientos
        // $features->setExtractSyntax(true);  // Habilita el análisis de sintaxis
        
        // Prepara la solicitud
        $request = new AnnotateTextRequest();
        $request->setDocument($document);
        $request->setFeatures($features);
        
        // Llama a la API y maneja posibles errores
        try {
            $response = $languageServiceClient->annotateText($request);
            return $response->serializeToJsonString();
        } catch (ApiException $ex) {
            printf('Call failed with message: %s' . PHP_EOL, $ex->getMessage());
        }
    }
}
