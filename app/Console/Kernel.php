<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        'App\Console\Commands\TrebImport',
        'App\Console\Commands\TrebSchema',
        'App\Console\Commands\TrebImportMaster',
        'App\Console\Commands\TrebDelete',
        'App\Console\Commands\TrebUpdate',
        'App\Console\Commands\TrebObject',
    ];

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }

    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            $request = Request::create('/vow/residential/update', 'GET');
            app()['Illuminate\Contracts\Http\Kernel']->handle($request);
        })->hourlyAt(10);

        $schedule->call(function () {
            $request = Request::create('/vow/condo/update', 'GET');
            app()['Illuminate\Contracts\Http\Kernel']->handle($request);
        })->hourlyAt(30);

        $schedule->call(function () {
           
            $request = Request::create('/remove-residential-deleted-rows', 'GET');
            app()['Illuminate\Contracts\Http\Kernel']->handle($request);
        })->hourlyAt(40);

        $schedule->call(function () {
            $request = Request::create('/remove-condo-deleted-rows', 'GET');
            app()['Illuminate\Contracts\Http\Kernel']->handle($request);            
        })->hourlyAt(50);

        // $schedule->call(function () {
            
        // })->everyTenMinutes();

        /* $schedule->command('treb:schema --resi --update')
            ->twiceDaily(1, 13)
            ->before(function () {
                Storage::append('crons-logs.txt', "Cron Start: update resi" . date("Y-m-d H:i:s") . "\n");
            })
            ->after(function () {
                Artisan::call('treb:update --resi');
                Artisan::call('treb:import-master --resi');
                Artisan::call('treb:delete --resi');
                Artisan::call('treb:object --resi');

                Storage::append('crons-logs.txt', "Cron END: update resi" . date("Y-m-d H:i:s") . "\n");
            })->withoutOverlapping();

        $schedule->command('treb:schema --com --update')
            ->twiceDaily(1, 13)
            ->before(function () {
                Storage::append('crons-logs.txt', "Cron Start: update com" . date("Y-m-d H:i:s") . "\n");
            })
            ->after(function () {
                Artisan::call('treb:update --com');
                Artisan::call('treb:import-master --com');
                Artisan::call('treb:delete --com');
                Artisan::call('treb:object --com');

                Storage::append('crons-logs.txt', "Cron END: update com" . date("Y-m-d H:i:s") . "\n");
            })->withoutOverlapping();

        $schedule->command('treb:schema --condo --update')
            ->twiceDaily(1, 13)
            ->before(function () {
                Storage::append('crons-logs.txt', "Cron Start: update condo" . date("Y-m-d H:i:s") . "\n");
            })
            ->after(function () {
                Artisan::call('treb:update --condo');
                Artisan::call('treb:import-master --condo');
                Artisan::call('treb:delete --condo');
                Artisan::call('treb:object --condo');

                Storage::append('crons-logs.txt', "Cron END: update condo" . date("Y-m-d H:i:s") . "\n");
            })->withoutOverlapping(); */

    }

}
