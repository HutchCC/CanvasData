<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Pool;
use GuzzleHttp\Exception\RequestException;
use Storage;
use App\Http\Controllers\ApiConnection;

class Sync extends Controller
{



    public function sync()
    {
        $this->downloadLatestSchemaFile();

        $syncMap = $this->getLatestSyncMap();
        $this->downloadAllFilesOnMap($syncMap);
        
        echo PHP_EOL . 'Deleting old files. ' . PHP_EOL . PHP_EOL;
        $this->deleteOldFiles($syncMap);
    }



    private function downloadLatestSchemaFile()
    {
        $api = new ApiConnection();
        $schema = $api->get('/api/schema/latest');
        Storage::disk('packedData')->put('schema.json', $schema);
    }



    private function getLatestSyncMap()
    {
        $api = new ApiConnection();
        return json_decode($api->get('/api/account/self/file/sync'), true);
    }

    private function downloadAllFilesOnMap($syncMap)
    {
        foreach ($syncMap['files'] as $file) {
            if (!Storage::disk('packedData')->exists('/' . $file['table'] .'/'. $file['filename'])) {
                echo $file['filename'] . ' does not exist.' . PHP_EOL;
                echo 'Downloading ' . $file['filename'] . PHP_EOL;
                $refreshedFile = $this->getRefreshedFileInfo($file);
                //echo $refreshedFile['url'] . PHP_EOL . PHP_EOL;
                $this->downloadFile($refreshedFile);
            } else {
                echo $file['filename'] . ' already exists. No need to download.' . PHP_EOL;
            }
        }
    }



    private function downloadFile($file)
    {
        $client = new Guzzle(['http_errors' => false]);
        $retries = 0;
        while ($retries < 5) {
            try {
                $response = $client->request('GET', $file['url']);
                if ($response->getStatusCode() == 200) {
                    Storage::disk('packedData')->put($file['table'] . '/' . $file['filename'], $response->getBody());
                    break;
                }
            } catch (RequestException $e) {
                echo Psr7\str($e->getRequest());
                if ($e->hasResponse()) {
                    echo Psr7\str($e->getResponse());
                }
            }

            echo 'Error during file download ' . $response->getStatusCode() . PHP_EOL;
            echo 'File url: ' . $file['url'] . PHP_EOL;
            echo 'Response ' . $response->getBody() . PHP_EOL;
            // var_dump($response->getHeaders());
            echo 'Retrying url' . PHP_EOL;
            echo PHP_EOL . PHP_EOL;

            $retries++;
            sleep(2);
        }
        
        unset($client);
    }



    private function getRefreshedFileInfo($originalFile)
    {
        $updatedSyncMap = $this->getLatestSyncMap();
        foreach ($updatedSyncMap['files'] as $refreshedFile) {
            if ($originalFile['filename'] == $refreshedFile['filename']) {
                return $refreshedFile;
            }
        }

        echo 'Error: Could not find refreshed file. Returned originalFile' . PHP_EOL;
        return $originalFile;
    }



    private function deleteOldFiles($syncMap)
    {
        $currentAPIFiles = $this->getCurrentAPIFiles($syncMap);
        $directoriesOnDisk = Storage::disk('packedData')->directories();
        foreach ($directoriesOnDisk as $directory) {

            $files = Storage::disk('packedData')->files($directory);
            foreach ($files as $file) {
                $file = pathinfo($file, PATHINFO_BASENAME);
                if (!in_array($file, $currentAPIFiles) && $file != 'schema.json' && $file != '.' && $file != '..') {
                    echo $file . ' deleted' . PHP_EOL;
                    Storage::disk('packedData')->delete($directory . '/' . $file);
                }
            }
        }
    }



    private function getCurrentAPIFiles($syncMap = null)
    {
        $syncMap = $syncMap ? : $this->getLatestSyncMap();
        
        $currentAPIFiles = array();
        foreach ($syncMap['files'] as $file) {
            array_push($currentAPIFiles, $file['filename']);
        }

        return $currentAPIFiles;
    }
}
