<?php
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

require_once __DIR__ . '/vendor/autoload.php';

$client = new Client();
$response = $client->request('GET', 'https://unicordoba.edu.co/noticias-historial/');
$html = (string) $response->getBody();

file_put_contents('unicordoba.html', $html);
echo "HTML saved to unicordoba.html\n";
