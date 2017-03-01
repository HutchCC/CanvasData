<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Storage;

class Unpack extends Controller
{

    //UNZIPS EACH ZIP FILES IN EACH TABLE DIRECTORY WRITES TO A .TXT FILE
    //(ONE.TXT FILE PER TABLE DIRECTORY)
    public function unpack()
    {
        $schemaJson = $this->getSchema();
        foreach ($schemaJson['schema'] as $table) {
            $tableName = $table['tableName'];
            $this->unpackFile($tableName);
        }
    }

    public function unpackFile($tableName)
    {
        $txtFile = $this->createFile($tableName);
        $this->convertZipToTxt($tableName, $txtFile);
    }

    private function getSchema()
    {
        $schemaFile = Storage::disk('packedData')->get('schema.json');
        $schemaJson = json_decode($schemaFile, true);
        unset($schemaFile);
        return $schemaJson;
    }



    //CREATES THE FILE THE ZIPPED CONTENT WILL UNZIP INTO
    //NO DATA FROM THE ZIPPED FILES ARE WITTEN TO FILE IN THIS METHOD
    //RETURNS .txt OPEN FILE HANDLER
    private function createFile($tableName)
    {
        //OPEN SCHEMA FILE AND CONVERT TO ARRAY
        if (!file_exists('./storage/canvasData/unpackedData')) {
            mkdir('./storage/canvasData/unpackedData');
        }
        $txtFile = fopen('./storage/canvasData/unpackedData/' . $tableName . '.txt', 'w');
        echo $tableName . '.txt file created.' . PHP_EOL;

        //returns open file handle to be written to later
        return $txtFile;
    }



    //UNZIPS ALL GZ FILES IN A DIRECTORY, APPENDS THE CONTENTS TO A SINGLE .TXT FILE
    //TAKES A TABLE NAME AS AN ARGUMENT (A DIRECTORY THAT HOLDS THE GZ FILES HAS THE SAME NAME AS THE TABLE IT ASSOCIATES WITH)
    private function convertZipToTxt($tableName, $txtFile)
    {
        $directory = './storage/canvasData/packedData/' . $tableName;
        if (is_dir($directory)) {
            $files = scandir($directory);
            
            //OPEN EACH ZIPPED FILE IN THE DIRECTORY, UNZIP, AND APPEND TO .TXT
            foreach ($files as $file) {
                if (substr($file, -3) == '.gz') {
                    $zipFile = gzopen($directory . '/' . $file, 'r');
                    echo 'Unzipping ' . $file . '. Writing to ' . $tableName . '.txt' . PHP_EOL;
                    
                    while (!gzeof($zipFile)) {
                        fwrite($txtFile, gzread($zipFile, 65536));
                    }
                    gzclose($zipFile);
                }
            }
            fclose($txtFile);
            echo $tableName . '.txt is complete.' . PHP_EOL . PHP_EOL;
        } else {
            fclose($txtFile);
            echo $directory . ' DOES NOT EXIST.' . PHP_EOL . PHP_EOL;
        }
    }
}
