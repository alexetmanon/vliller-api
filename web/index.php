<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';

const API_BASE = 'http://vlille.fr/stations/';

/**
 *
 * @param  String $xmlStationsString
 * @return Array
 */
function xmlStationsToJson($xmlStationsString)
{
    $stations = [];

    // fix encode error
    $xmlStationsString = preg_replace('/(<\?xml[^?]+?)utf-16/i', '$1utf-8', $xmlStationsString);

    $dom = new DOMDocument;
    $dom->loadXML($xmlStationsString);

    // builds array of stations from markers nodes list
    $markers = $dom->childNodes[0]->childNodes;
    foreach ($markers as $marker) {
        if ($marker->nodeType !== 1) {
            continue;
        }

        // use standard names like 'latitude'
        $stations[] = [
            'id' => $marker->getAttribute('id'),
            'name' => $marker->getAttribute('name'),
            'latitude' => floatval($marker->getAttribute('lat')),
            'longitude' => floatval($marker->getAttribute('lng'))
        ];
    }

    return $stations;
}

/**
 *
 * @param  String $xmlStationString
 * @return Array
 */
function xmlStationToJson($xmlStationString)
{
    // fix encode error
    $xmlStationString = preg_replace('/(<\?xml[^?]+?)utf-16/i', '$1utf-8', $xmlStationString);

    $dom = new DOMDocument;
    $dom->loadXML($xmlStationString);

    $stationElement = $dom->childNodes[0];

    // use standard names and fix bad english tranlation...
    $station = [
        'address' => $stationElement->getElementsByTagName('adress')[0]->nodeValue,
        'bikes' => $stationElement->getElementsByTagName('bikes')[0]->nodeValue,
        'docks' => $stationElement->getElementsByTagName('attachs')[0]->nodeValue,
        'payment' => $stationElement->getElementsByTagName('paiement')[0]->nodeValue,
        'status' => $stationElement->getElementsByTagName('status')[0]->nodeValue,
        'lastupd' => $stationElement->getElementsByTagName('lastupd')[0]->nodeValue
    ];

    return $station;
}

$app = new \Slim\App;

/**
 * Return all stations
 */
$app->get('/stations', function (Request $req, Response $res) {
    $client = new \GuzzleHttp\Client();

    $xmlStations = $client
        ->get(API_BASE . '/xml-stations.aspx', [
            'headers' => [
                'Accept' => 'application/xml'
            ]
        ])
        ->getBody()
        ->getContents();

    $stations = xmlStationsToJson($xmlStations);

    return $res->withJson($stations);
});

/**
 * Return a station by `id`
 */
$app->get('/stations/{id}', function (Request $req, Response $res) {
        $client = new \GuzzleHttp\Client();

    $xmlStation = $client
        ->get(API_BASE . '/xml-station.aspx', [
            'query' => [
                'borne' => $req->getAttribute('id')
            ],
            'headers' => [
                'Accept' => 'application/xml'
            ]
        ])
        ->getBody()
        ->getContents();

    $station = xmlStationToJson($xmlStation);

    return $res->withJson($station);
});


$app->run();