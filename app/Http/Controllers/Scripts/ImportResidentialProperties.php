<?php

namespace App\Http\Controllers\Scripts;

use App\Http\Controllers\Controller;
use App\Models\CronSetting;
use App\Models\ResidentialProperties;
use Illuminate\Http\Request;

ini_set("memory_limit", "-1");
set_time_limit(0);

class ImportResidentialProperties extends Controller
{
    protected $rets;
    public function __construct()
    {
        $config = new \PHRETS\Configuration;
        $config->setLoginUrl(env('RETS_LOGIN_URL'));
        $config->setUsername(env('RETS_USERNAME'));
        $config->setPassword(env('RETS_PASSWORD'));
		$config->setRetsVersion('1.7');
        $rets = new \PHRETS\Session($config);
        $this->rets = $rets;
    }

    /*
     ** Get Residential Properties
     */
    public function index(Request $request)
    {
        $className = 'ResidentialProperty';
        $cs = CronSetting::where('resource', $className)->first();
        $query = '(Status=|A,U)';
        if (!is_null($cs) && !$request->input('full')) {
            $time = toRetsTime($cs->last_execution);
            $query = "(Timestamp_sql=$time)";
        }

        $connect = $this->rets->Login();
        $results = $this->rets->Search('Property', $className, $query);
        $records_found = $results->getTotalResultsCount();

        if ($records_found > 0) {
            $PropertyData = $results->toArray();

            foreach ($PropertyData as $Data) {
                $lowecase_key_data = array_change_key_case($Data, CASE_LOWER);
                $filtered = array_filter($lowecase_key_data, function ($value) {return !is_null($value) && $value !== '';});
                $added = ResidentialProperties::updateOrCreate(
                    ['ml_num' => $filtered['ml_num']],
                    $filtered
                );

            }
        }

        $maxTime = ResidentialProperties::max('timestamp_sql');
        CronSetting::updateOrCreate(['resource' => $className], ['last_execution' => $maxTime]);
        \Log::info("Downloading ResidentialProperty completed. Records found $records_found ");
    }
}
