<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ApiConnection;
use App\Http\Requests;
use Carbon\Carbon;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Illuminate\Http\Request;
use Storage;

class Sync extends Controller
{
    private $syncMap = [];
    private $lastSyncTime;
    private $maxRetries = 5;
    private $awsTimeout = 5;

    public function __construct()
    {
        
    }

    public function sync()
    {
        $this->downloadLatestSchemaFile();

        $this->getLatestSyncMap();
        
        $this->downloadAllFilesOnMap();
        
        echo PHP_EOL . 'Deleting old files. ' . PHP_EOL . PHP_EOL;
        $this->deleteOldFiles();
    }



    private function downloadLatestSchemaFile()
    {
        $api = new ApiConnection();
        $schema = $api->get('/api/schema/latest');
        Storage::disk('packedData')->put('schema.json', $schema);

        return $this;
    }



    private function getLatestSyncMap()
    {
        // If syncmap is empty - refresh
        if (empty($this->syncMap)) {
            $this->lastSyncTime = Carbon::now();

            echo 'Fetching syncmap' . PHP_EOL;
            $api = new ApiConnection();
            $this->syncMap = json_decode($api->get('/api/account/self/file/sync'), true);
        }
        
        return $this;
    }

    private function downloadAllFilesOnMap()
    {
        $retries = 0;
        while ($retries < $this->maxRetries) {
            if ($retries > 0) {
                // Refresh syncMap
                echo 'An error occurred, attempting to retry. Retry count: ' . $retries . PHP_EOL;
                $this->syncMap = [];
                $this->getLatestSyncMap();
            }

            foreach ($this->syncMap['files'] as $file) {
                if (!Storage::disk('packedData')->exists('/' . $file['table'] .'/'. $file['filename'])) {
                    echo $file['filename'] . ' does not exist.' . PHP_EOL;

                    // Check to see if the timer has expired.
                    $now = Carbon::now();
                    if ($this->lastSyncTime->diffInMinutes($now) > $this->awsTimeout) {
                        break;
                    }

                    // Attempt to download the file
                    if (!$this->downloadFile($file)) {
                        break;
                    }
                } else {
                    echo $file['filename'] . ' already exists. No need to download.' . PHP_EOL;
                }
            }

            $retries++;
        }
    }



    private function downloadFile($file)
    {
        echo 'Downloading ' . $file['filename'] . ' from ' . $file['url']  . PHP_EOL;
        
        $client = new Guzzle(['http_errors' => false]);
        try {
            $response = $client->request('GET', $file['url']);
            if ($response->getStatusCode() == 200) {
                Storage::disk('packedData')->put($file['table'] . '/' . $file['filename'], $response->getBody());
            }
            return true;
        } catch (RequestException $e) {
            echo Psr7\str($e->getRequest());
            if ($e->hasResponse()) {
                echo Psr7\str($e->getResponse());
            }
            echo 'Error during file download ' . $response->getStatusCode() . PHP_EOL;
            echo 'File url: ' . $file['url'] . PHP_EOL;
            echo 'Response ' . $response->getBody() . PHP_EOL;

            return false;
        }
    }



    private function getRefreshedFileInfo($originalFile)
    {
        // $updatedSyncMap = $this->getLatestSyncMap();
        $this->getLatestSyncMap();
        foreach ($this->syncMap['files'] as $refreshedFile) {
            if ($originalFile['filename'] == $refreshedFile['filename']) {
                return $refreshedFile;
            }
        }

        echo 'Error: Could not find refreshed file. Returned originalFile' . PHP_EOL;
        return $originalFile;
    }



    private function deleteOldFiles()
    {
        $currentAPIFiles = $this->getCurrentAPIFiles($this->syncMap);
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



    private function getCurrentAPIFiles()
    {
        $this->getLatestSyncMap();
        
        $currentAPIFiles = array();
        foreach ($this->syncMap['files'] as $file) {
            array_push($currentAPIFiles, $file['filename']);
        }

        return $currentAPIFiles;
    }
}
