<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CanvasDataLoadTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'canvasdata:load_table {tableName=all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        if ($this->argument('tableName') == 'all') {
            return (new \App\Http\Controllers\DbAgent)->loadTables();
        }
        
        return (new \App\Http\Controllers\DbAgent)->loadTable($this->argument('tableName'));
    }
}
