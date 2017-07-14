<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CanvasDataUnpack extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'canvasdata:unpack {tableName=all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unzips the compressed files downloaded from canvasdata:sync command.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Unpack all tables
        if ($this->argument('tableName') == 'all') {
            return (new \App\Http\Controllers\Unpack)->unpack();
        }

        // Unpack a single table or array of tables
        $arguments = $this->argument();
        $tableNames = explode(',', $arguments['tableName']);
        foreach ($tableNames as $tableName) {
            (new \App\Http\Controllers\Unpack)->unpackFile($tableName);
        }
    }
}
