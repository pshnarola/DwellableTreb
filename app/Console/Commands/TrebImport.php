<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;

class TrebImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'treb:import {--resi} {--com} {--condo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import rets resources.';

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
        $resi = $this->option('resi');
        $com = $this->option('com');
        $condo = $this->option('condo');

        if ($resi) {
            $request = Request::create('/scripts/residential?full=1', 'GET');
        } else if ($com) {
            $request = Request::create('/scripts/commercial?full=1', 'GET');
        } else if ($condo) {
            $request = Request::create('/scripts/condo?full=1', 'GET');
        } else {
            $this->info('Class type is required: --resi --condo --com');
            die;
        }
        $this->info(app()['Illuminate\Contracts\Http\Kernel']->handle($request));
    }
}
