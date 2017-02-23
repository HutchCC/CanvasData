<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CanvasDataCreateSchema extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'canvasdata:create_schema';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Loads the uncompressed files into the configured database system.';

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
        return (new \App\Http\Controllers\DbAgent)->buildSchema();
    }
}
