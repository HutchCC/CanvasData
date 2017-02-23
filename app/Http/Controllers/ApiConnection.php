<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use GuzzleHttp\Client as Guzzle;

class ApiConnection extends Controller
{



    public function get($path) 
    {        
      $client = new Guzzle(['base_uri' => env('API_BASEURL'), 'http_errors' => false]);
      $http_headers = $this->createHeaders($path);
      $response = $client->request('GET', $path, ['headers' => $http_headers]);
      // $response = $client->request('GET', $u['path'] . $queryString, ['headers' => $http_headers]);
      return $response->getBody();
    }



    private function createHeaders($path) 
    {
        // Minimal information needed to generate signature
        $timestamp = gmdate( 'D, d M Y H:i:s T' );
        $u = parse_url( env('API_BASEURL') . $path );
        $queryString = '';
        if (!empty($u['query'])) 
        {
            $queryString = '?' . $u['query'];
        }

        // Generate signature
        $url = env('API_BASEURL') . $path;
        $api_secret = env('API_SECRET');
        $hmac = $this->hmac_signature( $timestamp, $url, $api_secret );

        // Headers to add to request
        $api_key = env('API_KEY');
        $http_headers = array ( 'Authorization' => 'HMACAuth ' . $api_key . ':' . $hmac, 
            'Date' => $timestamp);
        return $http_headers;
    }



    // This version is slimmed down to only include what is needed for the current API
    private function hmac_signature($timestamp = NULL, $url = NULL, $secret = NULL) {
          if (empty( $timestamp ) || empty( $url ) || empty( $secret )) {
            return;
          }

          $u = parse_url( $url );
          if ($u === FALSE) {
            return;
          }

          $host = ! empty( $u['host'] ) ? $u['host'] : 'portal.inshosteddata.com';
          $path = $u['path'];
          $query = '';
          if (! empty( $u['query'] )) {
            $parms = explode( '&', $u['query'] );
            sort( $parms );
            $query = join( '&', $parms );
          }

          $parts = array ( 'GET', $host, '', '', $path, $query, $timestamp, $secret );
          $message = join( "\n", $parts );

          return base64_encode( hash_hmac( 'sha256', $message, $secret, TRUE ) );
        }
}
