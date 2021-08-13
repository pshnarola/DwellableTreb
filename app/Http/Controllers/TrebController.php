<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PropertyAvailable;
use App\Models\Treb;

class TrebController extends Controller
{
    protected $rets;
    public function __construct()
    {
        $config = new \PHRETS\Configuration;
        $config->setLoginUrl(env('RETS_LOGIN_URL'));
        $config->setUsername(env('RETS_USERNAME'));
        $config->setPassword(env('RETS_PASSWORD'));
        $rets = new \PHRETS\Session($config);
        $this->rets = $rets;
    }

    public function resi()
    {
        Treb::createTable($this->getMeta('Property', 'ResidentialProperty'), 'residential_properties');
    }

    public function resiUpdate()
    {
        Treb::updateTable($this->getMeta('Property', 'ResidentialProperty'), 'residential_properties');
    }

    public function com()
    {
        Treb::createTable($this->getMeta('Property', 'CommercialProperty'), 'commercial_properties');
    }

    public function comUpdate()
    {
        Treb::updateTable($this->getMeta('Property', 'CommercialProperty'), 'commercial_properties');
    }

    public function condo()
    {
        Treb::createTable($this->getMeta('Property', 'CondoProperty'), 'condo_properties');
    }

    public function condoUpdate()
    {
        Treb::updateTable($this->getMeta('Property', 'CondoProperty'), 'condo_properties');
    }

    private function getMeta($resource, $class)
    {
        $connect = $this->rets->Login();
        $meta = $this->rets->GetTableMetadata($resource, $class)->toArray();
        $this->rets->Disconnect();
        return $meta;
    }

    private function switchUserForAvailableData()
    {
        $config = new \PHRETS\Configuration;
        $config->setLoginUrl(env('RETS_LOGIN_URL'));
        $config->setUsername(env('RETS_USERNAME') . "_a");
        $config->setPassword(env('RETS_PASSWORD'));
        $rets = new \PHRETS\Session($config);
        $this->rets = $rets;
    }

    public function resiAvaliableDownload()
    {
        $this->switchUserForAvailableData();
        $connect = $this->rets->Login();
        $results = $this->rets->Search('Property', 'ResidentialProperty', "(Status=|A,U)", ['Select' => 'ml_num']);
        PropertyAvailable::where('class_name', 'ResidentialProperty')->delete();
        foreach ($results as $item) {
            $arr = $item->toArray();
            PropertyAvailable::insert(['ml_num' => $arr['Ml_num'], 'class_name' => 'ResidentialProperty']);
        }
        echo "resiAvaliableDownload done\n";
        \Log::info("Downloaded residential available");
    }

    public function comAvaliableDownload()
    {
        $this->switchUserForAvailableData();
        $connect = $this->rets->Login();
        $results = $this->rets->Search('Property', 'CommercialProperty', "(Status=|A,U)", ['Select' => 'ml_num']);
        PropertyAvailable::where('class_name', 'CommercialProperty')->delete();
        foreach ($results as $item) {
            $arr = $item->toArray();
            PropertyAvailable::insert(['ml_num' => $arr['Ml_num'], 'class_name' => 'CommercialProperty']);
        }
        echo "comAvaliableDownload done\n";
        \Log::info("Downloaded commercial available");
    }

    public function condoAvaliableDownload()
    {
        $this->switchUserForAvailableData();
        $connect = $this->rets->Login();
        $results = $this->rets->Search('Property', 'CondoProperty', "(Status=|A,U)", ['Select' => 'ml_num']);
        PropertyAvailable::where('class_name', 'CondoProperty')->delete();
        foreach ($results as $item) {
            $arr = $item->toArray();
            PropertyAvailable::insert(['ml_num' => $arr['Ml_num'], 'class_name' => 'CondoProperty']);
        }
        echo "condoAvaliableDownload done\n";
        \Log::info("Downloaded commercial available");
    }

    public function resiRemoveExtraRows()
    {
        Treb::removeRows('ResidentialProperty', 'residential_properties');
    }

    public function comRemoveExtraRows()
    {
        Treb::removeRows('CommercialProperty', 'commercial_properties');
    }

    public function condoRemoveExtraRows()
    {
        Treb::removeRows('CondoProperty', 'condo_properties');
    }

    public function resiObject()
    {
        Treb::downloadObjects($this->rets, 'residential_properties');
    }

    public function comObject()
    {
        Treb::downloadObjects($this->rets, 'commercial_properties');
    }

    public function condoObject()
    {
        Treb::downloadObjects($this->rets, 'condo_properties');
    }

    public function cronlog()
    {
        echo "<pre>";
        echo file_get_contents(storage_path('app/crons-logs.txt'));
    }
    public function log()
    {
        echo "<pre>";
        echo file_get_contents(storage_path('logs/laravel-' . date("Y-m-d") . '.log'));
    }
    public function clearlog()
    {
        file_put_contents(storage_path('app/crons-logs.txt'), "");
        file_put_contents(storage_path('logs/laravel-' . date("Y-m-d") . '.log'), "");
        echo 'Success all logs were removed!';
    }

    public function searchMlsResi($mlsid)
    {
        print_r("Query: " . "(ml_num=$mlsid)");
        $connect = $this->rets->Login();
        $results = $this->rets->Search('Property', 'ResidentialProperty', "(ml_num=$mlsid)");
        echo '<pre>';
        echo "Total Records Count: " . $results->getTotalResultsCount();
        echo "<hr> Items: ";
        print_r($results->toArray());
    }
	
	public function countResultByType($type)
    {
        $connect = $this->rets->Login();
        $results = $this->rets->Search('Property', $type, "(status=A|U)");
        echo '<pre>';
        echo "Total Records Count: " . $results->getTotalResultsCount();
		die;
    }

    public function soldCondoObject(){
        Treb::downloadSoldObjects('sold_condo_properties');
    }

    public function soldResidentialObject(){
        Treb::downloadSoldObjects('sold_residential_properties');
    }
}
