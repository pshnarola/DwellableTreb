<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;

class TrebSchema extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'treb:schema {--resi} {--com} {--condo} {--update}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create schema for resource';

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
        $update = $this->option('update');

        if ($resi) {
            if ($update) {
                $request = Request::create('/schema/residential/update', 'GET');
            } else {
                $request = Request::create('/schema/residential/create', 'GET');
            }
        } else if ($com) {
            if ($update) {
                $request = Request::create('/schema/commercial/update', 'GET');
            } else {
                $request = Request::create('/schema/commercial/create', 'GET');
            }
        } else if ($condo) {
            if ($update) {
                $request = Request::create('/schema/condo/update', 'GET');
            } else {
                $request = Request::create('/schema/condo/create', 'GET');
            }
        } else {
            $this->info('Class type is required: --resi --condo --com');
            die;
        }
        $this->info(app()['Illuminate\Contracts\Http\Kernel']->handle($request));
    }
}
