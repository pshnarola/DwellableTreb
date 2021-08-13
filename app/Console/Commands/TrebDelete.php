<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;

class TrebDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'treb:delete {--resi} {--com} {--condo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete extra records by comparing with the master avaliable data';

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
            $request = Request::create('/available/residential/remove', 'GET');
        } else if ($com) {
            $request = Request::create('/available/commercial/remove', 'GET');
        } else if ($condo) {
            $request = Request::create('/available/condo/remove', 'GET');
        } else {
            $this->info('Class type is required: --resi --condo --com');
            die;
        }
        $this->info(app()['Illuminate\Contracts\Http\Kernel']->handle($request));
    }
}
