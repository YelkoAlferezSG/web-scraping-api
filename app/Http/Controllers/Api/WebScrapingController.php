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
        // Cargar la API Key desde el archivo .env
        $apiKey = env('GOOGLE_API_KEY');

        // Verificar si la API Key está configurada
        if (!$apiKey) {
            return response()->json([
                'error' => 'La API Key de Google Cloud no está configurada correctamente.'
            ], 500);
        }

        // Crear una instancia del cliente de Google con la API Key
        $client = new Google_Client();
        $client->setDeveloperKey($apiKey);

        // Crear el servicio de lenguaje de Google
        $language = new Google_Service_Language($client);

        // Realizar un análisis de entidades sobre el texto del cuerpo
        $document = new \Google_Service_Language_Document([
            'content' => $bodyText,
            'type' => 'PLAIN_TEXT',
        ]);

        $response = $language->documents->analyzeEntities($document);

        // Inicializa el array de palabras clave
        $keywords = [];

        // Recorre las entidades extraídas del análisis
        foreach ($response->getEntities() as $entity) {
            // Filtra por entidades que tengan una alta relevancia (esto puede ajustarse según sea necesario)
            if ($entity->getSalience() > 0.1) { // Umbral de saliencia
                $keywords[] = $entity->getName();
            }
        }

        // Elimina duplicados en el array de keywords
        return array_unique($keywords);
    }
}
