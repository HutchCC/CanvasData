<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CanvasDataSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'canvasdata:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize all of the CanvasData compressed files.';

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
        return (new \App\Http\Controllers\Sync)->sync();
    }
}
