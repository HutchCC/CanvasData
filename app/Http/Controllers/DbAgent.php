<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use CanvasData\SchemaBuilder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DbAgent extends Controller
{

    //CREATES SCHEMABUILDER CLASS
    //SCHEMABUILDER CLASS THEN BUILDS MYSQL DATABASE TABLES AND COLUMNS
    public function buildSchema()
    {
        echo "Writing SchemaBuilder Class" . PHP_EOL;
        $this->createSchemaBuilderClass();

        echo "Building Schema" . PHP_EOL;
        $schemaBuilder = new SchemaBuilder;

        $schemaBuilder->buildSchema();
        echo 'Schema Built' . PHP_EOL;
    }



    //CREATES A SCHEMABUILDER CLASS IN APP/SCHEMABUILDER
    //SCHEMABUILDER CLASS IS USED TO BUILD THE MYSQL SCHEMA
    private function createSchemaBuilderClass()
    {
        $schemaJson = $this->getSchema();

        $filename = 'SchemaBuilder.php';
        // $migrationFile = fopen('./app/SchemaBuilder/' . $filename, 'w');

        //BEGINS WRITTING THE CLASS TO FILE
        Storage::disk('canvasData')->put(
            $filename,
            '<?php

            namespace CanvasData;

            use Illuminate\Database\Schema\Blueprint;
            use Illuminate\Database\Migrations\Migration;

            class SchemaBuilder extends Migration
            {
                /**
                 * Run the migrations.
                 *
                 * @return void
                 */
                public function buildSchema()
                {'
        );
        
        foreach ($schemaJson['schema'] as $tableKeys) {
            $tableName = $tableKeys['tableName'];
            $columnsArray = $tableKeys['columns'];
            
            Storage::disk('canvasData')->append(
                $filename,
                '
                if (\Schema::hasTable("' . $tableName . '"))
                {
                    \Schema::drop("' . $tableName . '");
                }

                \Schema::create("' . $tableName . '", function(Blueprint $table)
                {
                    $table->engine = "InnoDB";'
            );
            


            //APPENDED NEW LINE FOR EACH COLUMN IN THE TABLE
            foreach ($columnsArray as $column => $value) {
                $translatedType = $this->translateTypeToLaravel($columnsArray[$column]['type']);

                if ($translatedType == 'enum') {
                    Storage::disk('canvasData')->append(
                        $filename,
                        '
                        $table->'. $translatedType . '("' . $columnsArray[$column]['name'] . '", ["true", "false"])->nullable();'
                    );
                } elseif ($translatedType != 'enum') {
                    Storage::disk('canvasData')->append(
                        $filename,
                        '
                        $table->'. $translatedType . '("' . $columnsArray[$column]['name'] .'")->nullable();'
                    );
                }
            }

            Storage::disk('canvasData')->append(
                $filename,
                '
                });'
            );
        }
        Storage::disk('canvasData')->append($filename, '
            }
        }');
    }



    //TRANSLATES DATATYPES FROM SCHEMA.JSON FILE TO LARAVEL'S DATATYPE
    private function translateTypeToLaravel($datatype)
    {
        switch ($datatype) {
            case 'varchar':
                $translatedType = 'string';
                break;
            case 'timestamp':
                $translatedType = 'timestamp';
                break;
            case 'double precision':
                $translatedType = 'double';
                break;
            case 'date':
                $translatedType = 'date';
                break;
            case 'enum':
                $translatedType = 'string';
                break;
            case 'integer':
                $translatedType = 'integer';
                break;
            case 'bigint':
                $translatedType = 'bigInteger';
                break;
            case 'text':
                $translatedType = 'mediumText';
                break;
            case 'boolean':
                // booleans come in as strings from the unpacked files.
                // using enum prevent db from throwning an error when it recieves a string.
                $translatedType = 'enum';
                break;
            case 'int':
                $translatedType = 'integer';
                break;
            case 'guid':
                $translatedType = 'string';
                break;
            case 'time':
                $translatedType = 'time';
                break;
            default:
                $translatedType = 'string';
        }
        return $translatedType;
    }

    

    private function getSchema()
    {
        $schemaFile = Storage::disk('packedData')->get('schema.json');
        $schemaJson = json_decode($schemaFile, true);
        unset($schemaFile);
        return $schemaJson;
    }



    //LOADS UNPACKED DATA FILES INTO COORESPONDING MYSQL TABLES
    public function loadTables()
    {
        $schemaJson = $this->getSchema();
        foreach ($schemaJson['schema'] as $table) {
            $tableName = $table['tableName'];
            $this->loadTable($tableName);
        }
    }

    public function loadTable($tableName)
    {
        try {
                echo 'Loading data into ' . $tableName . PHP_EOL;
                \DB::table($tableName)->truncate();
                \DB::unprepared(\DB::raw("set bulk_insert_buffer_size= 1024 * 1024 * 256;"));
                \DB::unprepared(\DB::raw("alter table $tableName disable keys;"));
                \DB::unprepared(\DB::raw("LOAD DATA LOCAL INFILE '".storage_path('canvasData/unpackedData')."/$tableName.txt' INTO TABLE " . $tableName . " ;"));
                \DB::unprepared(\DB::raw("alter table $tableName enable keys;"));
        } catch (Exception $e) {
                echo 'Caught exception: ' .  $e->getMessage() . PHP_EOL;
        }
    }

    public function addIndexes()
    {
        $schemaJson = $this->getSchema();
        foreach ($schemaJson['schema'] as $table) {
            $columnsArray = $table['columns'];
            $tableName = $table['tableName'];
            echo $tableName . PHP_EOL;
 
            foreach ($columnsArray as $column) {
                echo '-Checking ' . $column['name'] . ' column' . PHP_EOL;
                if ($column['name'] == 'id') {
                    \Schema::table($tableName, function (Blueprint $table) use ($column) {
                        $table->index($column['name'], $column['name']);
                    });

                    \DB::statement(\DB::raw("OPTIMIZE TABLE " . $tableName . ";"));
                    echo '**----Indexed ' . $tableName . ' on column named ' . $column['name'] . PHP_EOL;
                }
            }
        }
    }
}
