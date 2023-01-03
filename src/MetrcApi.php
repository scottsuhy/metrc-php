<?php

namespace MetrcApi;

use MetrcApi\Exception\AccessDeniedException;
use MetrcApi\Exception\InvalidMetrcResponseException;
use MetrcApi\Models\ApiObject;
use MetrcApi\Models\Facility;
use MetrcApi\Models\Location; //SBS ADDED
use MetrcApi\Models\Harvest;
use MetrcApi\Models\HarvestPackage;
use MetrcApi\Models\HarvestWaste;
use MetrcApi\Models\Item;
use MetrcApi\Models\ItemCategory;
use MetrcApi\Models\LabTest;
use MetrcApi\Models\Package;
use MetrcApi\Models\Package2;
use MetrcApi\Models\PackageAdjustment;
use MetrcApi\Models\PackageChangeItem;
use MetrcApi\Models\PackageFinish;
use MetrcApi\Models\Plant;
use MetrcApi\Models\PlantBatch;
use MetrcApi\Models\PlantBatchDestruction;
use MetrcApi\Models\PlantBatchPlanting;
use MetrcApi\Models\PlantBatchPlantingGrowthPhase;
use MetrcApi\Models\PlantHarvest;
use MetrcApi\Models\Room;
use MetrcApi\Models\SalesReceipt;
use MetrcApi\Models\Strain;
use MetrcApi\Models\Transfer;

use Illuminate\Support\Facades\Log;

class MetrcApi
{
    const SANDBOX_URL = 'https://sandbox-api-%state%.metrc.com';
    const PRODUCTION_URL = 'https://api-%state%.metrc.com';

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $licenseNumber;

    /**
     * @var bool|null
     */
    private $sandbox;

    /**
     * @var string
     */
    private $method = 'GET';

    /**
     * @var string
     */
    private $route = null;

    /**
     * @var string
     */
    private $state;

    private $queryParams;

    /**
     * MetrcApi constructor.
     * @param string $username
     * @param string $password
     * @param string $licenseNumber
     * @param bool $sandbox
     * @param string $state
     */
    public function __construct(string $username, string $password, string $licenseNumber, bool $sandbox = false, string $state = 'ca')
    {
        $this->username      = $username;
        $this->password      = $password;
        $this->licenseNumber = $licenseNumber;
        $this->sandbox       = $sandbox;
        $this->queryParams   = [
            'licenseNumber' => $this->licenseNumber
        ];
        $this->state = $state;
    }

    /**
     * @param bool|ApiObject $obj
     * @return MetrcApiResponse
     * @throws \Exception|InvalidMetrcResponseException
     */
    private function executeAction($obj = false): MetrcApiResponse
    {
        //dd($obj);
        $base = $this->sandbox ? self::SANDBOX_URL : self::PRODUCTION_URL;
        $base = str_replace('%state%', $this->state, $base);

        $ch = curl_init($base.$this->route.'?'.http_build_query($this->queryParams));
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                $this->getAuthenticationHeader(),
                'Content-Type: application/json'
            ],
            CURLOPT_RETURNTRANSFER => 1
        ]);                

        if($this->method != 'GET') {
            if($this->method == 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);                
            } else {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($this->method));
            }
            if($obj) {
                if(is_iterable($obj)) {
                    $objects = [];
                    /** @var ApiObject $o */
                    foreach($obj as $o) {
                        $objects[] = $o;
                    }
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                        $objects
                    ]));

                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($obj->toArray()));                    
                }
            }
        }

        if(env('METRC_API_LOGGING')){
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            $streamVerboseHandle = fopen('php://temp', 'w+');
            curl_setopt($ch, CURLOPT_STDERR, $streamVerboseHandle);

            if($obj) {
                if(is_iterable($obj)) {
                    Log::info("MetricApi@executeAction (before call to METRC)", [
                        'URL' => curl_getinfo($ch,CURLINFO_EFFECTIVE_URL)                        
                    ]);  
                } else {
                    Log::info("MetricApi@executeAction (before call to METRC)", [
                        'URL' => curl_getinfo($ch,CURLINFO_EFFECTIVE_URL),                            
                        'objects' => json_encode($obj->toArray())                
                    ]); 
                }
            }
        }

        //some POSTS to METRC take forever and result in a 504.  code below is to try to help with this issue
        ini_set('max_execution_time',0);
        ini_set('request_terminate_timeout',0);                
        set_time_limit(300); // The maximum execution time, in seconds. If set to zero, no time limit is imposed.        
        ignore_user_abort(true); // Make sure to keep alive the script when a client disconnects.        
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300); //Tell cURL that it should only spend 300 seconds trying to connect to the URL in question.        
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); //A given cURL operation should only take 300 seconds max.

        $result = curl_exec($ch);

        if($this->method != 'GET') {
            if(env('METRC_API_LOGGING')){                

                $curl_verbose_message1 = "Curl error: " . curl_errno($ch) . htmlspecialchars(curl_error($ch));
                rewind($streamVerboseHandle);
                $verboseLog = stream_get_contents($streamVerboseHandle);                
                $curl_verbose_message2 = "Curl verbose information: " . htmlspecialchars($verboseLog);    
                Log::info("MetricApi@executeAction (POST request)", [
                    'URL' => curl_getinfo($ch,CURLINFO_EFFECTIVE_URL),            
                    'objects' => json_encode($obj->toArray()),
                    'CURL API result' => $result,
                    'curl_verbose_message1' => $curl_verbose_message1,
                    'curl_verbose_message2' => $curl_verbose_message2
                ]);                       

            }
        }
        else{
            if(env('METRC_API_LOGGING')){
                Log::info("MetricApi@executeAction (GET request)", [
                        'URL' => curl_getinfo($ch,CURLINFO_EFFECTIVE_URL),
                        'CURL API result' => $result                                
                ]);
            }
        }

        $response = new MetrcApiResponse();
        $response->setRawResponse($result);
        $response->setHttpCode(curl_getinfo($ch, CURLINFO_HTTP_CODE));

        if(env('METRC_API_LOGGING')){
            Log::info("MetricApi@executeAction (CURL response)", [
                    '$response->success' => $response->success,
                    '$response' => $response
            ]);
        }

//SBS Changed
  /*      if($response->getHttpCode() == 401) {
            throw new AccessDeniedException();
        } elseif($response->getHttpCode() == 500) {
            throw new InvalidMetrcResponseException(isset($response->getResponse()['Message']) ? $response->getResponse()['Message'] : 'API Response Returned 500 error!');
        }
*/
        return $response;
    }

    private function getAuthenticationHeader()
    {
        return sprintf("Authorization: Basic %s", base64_encode($this->username.":".$this->password));
    }

    private function mapResponseToObject(MetrcApiResponse $response, $class)
    {
        $obj = new $class;

        foreach($response->getResponse() as $k => $v) {
            $method = sprintf('set%s', ucwords($k));
            $obj->{$method}($v);
        }

        return $obj;
    }

    private function mapResponseToObjectArray(MetrcApiResponse $response, $class)
    {
        $arr = [];

        $responseArray = $response->getResponse();

        if(is_null($responseArray)){
            return $arr;
        }

        //Log::info("MetricApi@mapResponseToObjectArray", ['$responseArray'=>$responseArray]);

        foreach($responseArray as $k => $v) {
            $arr[$k] = new $class;
            //Log::info("MetricApi@inside foreach 1", ['$arr[$k]'=>$arr[$k], '$k'=>$k]);
            foreach($responseArray[$k] as $k2 => $v2) {                
                $method = sprintf('set%s', ucwords($k2));
                //Log::info("MetricApi@inside foreach 2", ['$method'=>$method, '$k2'=>$k2]);
                $arr[$k]->{$method}($v2);
                //Log::info("MetricApi@inside foreach 2", ['$v2'=>$v2]);
            }
        }

        return $arr;
    }

    /**
     * Get an array of facilities
     *
     * @see https://api-ca.metrc.com/Documentation/#Facilities.get_facilities_v1
     * @return array|null
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function getFacilities(): ?array
    {
        $this->route = '/facilities/v1/';
        $response = $this->executeAction();
       //Log::info("MetricApi@getFacilities (response)", ['$response'=>$response]);
        return $this->mapResponseToObjectArray($response, Facility::class);
    }

    //SBS Added
    public function getLocations(): ?array
    {        
        $this->route = '/locations/v1/active/';
        $response = $this->executeAction();
       //Log::info("MetricApi@getLocations (response)", ['$response'=>$response]);
        return $this->mapResponseToObjectArray($response, Location::class);
    }
    //SBS Added
    public function getLocation($id)//: ?array
    {
        $this->route = '/locations/v1/' . $id;
        //Log::info("MetricApi@getLocation (request sent)", ['$id'=>$id]);
        $response = $this->executeAction();
       //Log::info("MetricApi@getLocation (response)", ['$response'=>$response]);
        return $response; // $this->mapResponseToObject($response, Location::class);
    }
    //SBS Added
    public function createLocation(Location $location): MetrcApiResponse
    {
        $this->route = '/locations/v1/create';
        $this->method = 'POST';

        //Log::info("MetricApi@createLocation (request sent)", ['$location'=>$location]);
        $response = $this->executeAction($location);
       //Log::info("MetricApi@createLocation (response)", ['$response'=>$response]);

        //dd($response);
        return $response;
    }
    //SBS Added    
    public function updateLocation(Location $location): MetrcApiResponse
    {
        $this->route = '/locations/v1/update';
        $this->method = 'POST';
        //Log::info("MetricApi@updateLocation (request sent)", ['$location'=>$location]);
        $response = $this->executeAction($location);
       //Log::info("MetricApi@updatteLocation (response)", ['$response'=>$response]);
        return $response;
    }


    /**
     * Get an array of harvests
     *
     * @see https://api-ca.metrc.com/Documentation/#Harvests.get_harvests_v1_active
     * @param string $type type filter (active|onhold|inactive)
     * @return array|null
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function getHarvests($type = 'active', \DateTimeInterface $startDate = null, \DateTimeInterface $stopDate = null): ?array
    {
        $this->route = '/harvests/v1/' . $type; 

        if($startDate && $stopDate) {
            $this->queryParams['lastModifiedStart'] = $startDate->format(\DateTime::ISO8601);
            $this->queryParams['lastModifiedEnd']   = $stopDate->format(\DateTime::ISO8601);
        }

        $response = $this->executeAction();
       //Log::info("MetricApi@getHarvests (response)", ['$response'=>$response]);
        return $this->mapResponseToObjectArray($response, Harvest::class);
    }

    /**
     * Create a package from a harvest
     *
     * @see https://api-ca.metrc.com/Documentation/#Harvests.post_harvests_v1_createpackages
     * @param HarvestPackage $package
     * @return MetrcApiResponse
     * @throws InvalidMetrcResponseException
     */
    public function createHarvestPackage(HarvestPackage $package): MetrcApiResponse
    {
        $this->route = '/harvests/v1/create/packages';
        $this->method = 'POST';
        //Log::info("MetricApi@createHarvestPackage (before executeAction)", ['$package'=>$package]);
        $response = $this->executeAction($package);
       //Log::info("MetricApi@createHarvestPackage (response)", ['$response'=>$response]);
        return $response;
    }

    /**
     * Create waste from a Harvest
     *
     * @see https://api-ca.metrc.com/Documentation/#Harvests.post_harvests_v1_removewaste
     * @param HarvestWaste $waste
     * @return MetrcApiResponse
     * @throws InvalidMetrcResponseException
     */
    public function createHarvestWaste(HarvestWaste $waste): MetrcApiResponse
    {
        $this->route = '/harvests/v1/removewaste';
        $this->method = 'POST';
        $response = $this->executeAction($waste);
       //Log::info("MetricApi@createHarvestWaste (response)", ['$response'=>$response]);
        return $response;
    }

    /**
     * Finish/Discontinue a Harvest
     *
     * @see https://api-ca.metrc.com/Documentation/#Harvests.post_harvests_v1_finish
     * @param Harvest $harvest
     * @return MetrcApiResponse
     * @throws InvalidMetrcResponseException
     */
    public function finishHarvest(Harvest $harvest): MetrcApiResponse
    {
        $this->route = '/harvests/v1/finish';
        $this->method = 'POST';
        $response = $this->executeAction($harvest);
       //Log::info("MetricApi@finishHarvest (response)", ['$response'=>$response]);
        return $response;
    }

    /**
     * UnFinish/Continue a Harvest
     *
     * @see https://api-ca.metrc.com/Documentation/#Harvests.post_harvests_v1_unfinish
     * @param Harvest $harvest
     * @return MetrcApiResponse
     * @throws InvalidMetrcResponseException
     */
    public function unfinishHarvest(Harvest $harvest): MetrcApiResponse
    {
        $this->route = '/harvests/v1/unfinish';
        $this->method = 'POST';
        $response = $this->executeAction($harvest);
       //Log::info("MetricApi@unfinishHarvest (response)", ['$response'=>$response]);
        return $response;
    }

    /**
     * Get information about an item
     *
     * @see https://api-ca.metrc.com/Documentation/#Harvests.get_harvests_v1_{id}
     * @param string $id
     * @return array|null
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function getItem($id): ?Item
    {
        $this->route = '/items/v1/' . $id;
        $response = $this->executeAction();
       //Log::info("MetricApi@getItem (response)", ['$response'=>$response]);
        return $this->mapResponseToObject($response, Item::class);
    }

    /**
     * Get an array of items
     *
     * https://api-ca.metrc.com/Documentation/#Harvests.get_harvests_v1_active
     * @param string $type type filter (active|onhold|inactive)
     * @return array|null
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function getItems($type = 'active'): ?array
    {
        $this->route = '/items/v1/' . $type;
        $response = $this->executeAction();
       //Log::info("MetricApi@getItems (response)", ['$response'=>$response]);
        return $this->mapResponseToObjectArray($response, Item::class);
    }

    /**
     * Create an item
     *
     * @see https://api-ca.metrc.com/Documentation/#Items.post_items_v1_create
     * @param Item $item
     * @return MetrcApiResponse
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function createItem(Item $item): MetrcApiResponse
    {
        $this->route = '/items/v1/create';
        $this->method = 'POST';
        $response = $this->executeAction($item);
       //Log::info("MetricApi@createItem (response)", ['$response'=>$response]);
        return $response;
    }

    /**
     * Update an item
     *
     * @see https://api-ca.metrc.com/Documentation/#Items.post_items_v1_update
     * @param Item $item
     * @return MetrcApiResponse
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function updateItem(Item $item): MetrcApiResponse
    {
        $this->route = '/items/v1/update';
        $this->method = 'POST';
        $response = $this->executeAction($item);
       //Log::info("MetricApi@updateItem (response)", ['$response'=>$response]);
        return $response;
    }

    /**
     * Delete an item
     *
     * @see https://api-ca.metrc.com/Documentation/#Items.delete_items_v1_{id}
     * @param int $id
     * @return MetrcApiResponse
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function deleteItem(int $id): MetrcApiResponse
    {
        $this->route = '/items/v1/' . $id;
        $this->method = 'DELETE';
        $response = $this->executeAction();
       //Log::info("MetricApi@deleteItem (response)", ['$response'=>$response]);
        return $response;
    }

    /**
     * Get an array of item categories
     *
     * @see https://api-ca.metrc.com/Documentation/#Items.get_items_v1_categories
     * @return array|null
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function getItemCategories(): ?array
    {
        $this->route = '/items/v1/categories';
        $response = $this->executeAction();
        return $this->mapResponseToObjectArray($response, ItemCategory::class);
    }

    /**
     * Get information about a package
     *
     * @see https://api-ca.metrc.com/Documentation/#Packages.get_packages_v1_{id}
     * @param $id
     * @return Package|null
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function getPackage($id): MetrcApiResponse //SBS changed ?Package
    {
        $this->route = '/packages/v1/' . $id;
        $response = $this->executeAction();
       //Log::info("MetricApi@getPackage (response)", ['$response'=>$response]);
/*
        if(!$response->success){
            $message = "ERROR: " . $response->httpCode . " " . $response->rawResponse;
            dd($message);
        }

        return $this->mapResponseToObject($response, Package::class);
*/
        return $response;        
    }

    /**
     * Adjust a package
     *
     * @see https://api-ca.metrc.com/Documentation/#Packages.post_packages_v1_adjust
     * @param PackageAdjustment $package
     * @return MetrcApiResponse
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function adjustPackage(PackageAdjustment $package): MetrcApiResponse
    {
        $this->route = '/packages/v1/adjust';
        $this->method = 'POST';
        $response = $this->executeAction($package);
        return $response;
    }

    /**
     * Change a package item
     *
     * @see https://api-ca.metrc.com/Documentation/#Packages.post_packages_v1_change_item
     * @param PackageChangeItem $package
     * @return MetrcApiResponse
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function changePackageItem(PackageChangeItem $package): MetrcApiResponse
    {
        $this->route = '/packages/v1/change/item';
        $this->method = 'POST';
        $response = $this->executeAction($package);
       //Log::info("MetricApi@changePackageItem (response)", ['$response'=>$response]);
        return $response;
    }

    /**
     * Finish a package
     *
     * @see https://api-ca.metrc.com/Documentation/#Packages.post_packages_v1_finish
     * @param PackageFinish $package
     * @return MetrcApiResponse
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function finishPackage(PackageFinish $package): MetrcApiResponse
    {
        $this->route = '/packages/v1/finish';
        $this->method = 'POST';
        $response = $this->executeAction($package);
        return $response;
    }

    /**
     * UnFinish a package
     *
     * @see https://api-ca.metrc.com/Documentation/#Packages.post_packages_v1_unfinish
     * @param PackageFinish $package
     * @return MetrcApiResponse
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function unfinishPackage(PackageFinish $package): MetrcApiResponse
    {
        $this->route = '/packages/v1/unfinish';
        $this->method = 'POST';
        $response = $this->executeAction($package);
        return $response;
    }

    /**
     * Get an array of packages
     *
     * @see https://api-ca.metrc.com/Documentation/#Packages.get_packages_v1_active
     * @param string $type type filter (active|onhold|inactive)
     * @return array
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function getPackages($type = 'active'): ?array
    {
        $this->route = '/packages/v1/' . $type;
        $response = $this->executeAction();
        //Log::info("MetricApi@getPackages (response)", ['$response'=>$response]);
        return $this->mapResponseToObjectArray($response, Package::class);
    }

    /**
     * Create a package
     *
     * @see https://api-ca.metrc.com/Documentation/#Packages.post_packages_v1_create
     * @param Package $package
     * @return MetrcApiResponse
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function createPackage(Package2 $package): MetrcApiResponse
    {
        $this->route = '/packages/v1/create';
        $this->method = 'POST';
        //dd($package);        
        $response = $this->executeAction($package);
       //Log::info("MetricApi@createPackage (response)", ['$response'=>$response]);
        return $response;
    }

    /**
     * Get an array of package types
     *
     * @see https://api-ca.metrc.com/Documentation/#Packages.get_packages_v1_types
     * @return array
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function getPackageTypes(): ?array
    {
        $this->route = '/packages/v1/types';
        $response = $this->executeAction();
        return $response->getResponse();
    }

    /**
     * Get information about a plant batch
     *
     * @see https://api-ca.metrc.com/Documentation/#PlantBatches.get_plantbatches_v1_{id}
     * @param int|null $id
     * @return PlantBatch|null
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function getPlantBatch($id): ?PlantBatch
    {
        $this->route = '/plantbatches/v1/' . $id;
        $response = $this->executeAction();
        return $this->mapResponseToObject($response, PlantBatch::class);
    }

    /**
     * Get an array of plant batches
     *
     * @see https://api-ca.metrc.com/Documentation/#PlantBatches.get_plantbatches_v1_active
     * @param string $type type filter (active|inactive)
     * @return array
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function getPlantBatches($type = 'active', \DateTimeInterface $startDate = null, \DateTimeInterface $stopDate = null): ?array
    {
        $this->route = '/plantbatches/v1/' . $type;

        if($startDate && $stopDate) {
            $this->queryParams['lastModifiedStart'] = $startDate->format(\DateTime::ISO8601);
            $this->queryParams['lastModifiedEnd']   = $stopDate->format(\DateTime::ISO8601);
        }

        $response = $this->executeAction();
        return $this->mapResponseToObjectArray($response, PlantBatch::class);
    }

    

    /**
     * Create a planting in a batch
     *
     * @see https://api-ca.metrc.com/Documentation/#PlantBatches.post_plantbatches_v1_createplantings
     * @param PlantBatchPlanting $planting
     * @return MetrcApiResponse
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function createPlanting(PlantBatchPlanting $planting): MetrcApiResponse
    {
        $this->route = '/plantbatches/v1/createplantings';
        $this->method = 'POST';
        $response = $this->executeAction($planting);
        return $response;
    }

    /**
     * Create an array of plantings in a batch
     *
     * @see https://api-ca.metrc.com/Documentation/#PlantBatches.post_plantbatches_v1_createplantings
     * @param array $plantings
     * @return MetrcApiResponse
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function createPlantings(array $plantings): MetrcApiResponse
    {
        $this->route = '/plantbatches/v1/createplantings';
        $this->method = 'POST';
        $response = $this->executeAction(
            $plantings
        );
        return $response;
    }

    //SBS added
    public function splitPlantBatch(PlantBatch $plantbatch): MetrcApiResponse
    {
        $this->route = '/plantbatches/v1/split';
        $this->method = 'POST';
        $response = $this->executeAction($plantbatch);
       //Log::info("MetricApi@splitPlantBatch (response)", ['$response'=>$response]);
        return $response;
    }

    public function createPackageFromMotherPlant(PlantBatch $plantbatch): MetrcApiResponse
    {
        $this->route = '/plantbatches/v1/create/packages/frommotherplant';
        $this->method = 'POST';
        $response = $this->executeAction($plantbatch);
       //Log::info("MetricApi@createPackageFromMotherPlant (response)", ['$response'=>$response]);
        return $response;
    }

    public function createPackagesFromPlantBatch(PlantBatch $plantbatch): MetrcApiResponse
    {
        $this->route = '/plantbatches/v1/createpackages';
        $this->method = 'POST';
        $response = $this->executeAction($plantbatch);
       //Log::info("MetricApi@createPackagesFromPlantBatch (response)", ['$response'=>$response]);
        return $response;
    }

    /**
     * Change the growth phase of a plant batch
     *
     * @see https://api-ca.metrc.com/Documentation/#PlantBatches.post_plantbatches_v1_changegrowthphase
     * @param PlantBatchPlantingGrowthPhase $planting
     * @return MetrcApiResponse
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function changeGrowthPhase(PlantBatchPlantingGrowthPhase $planting): MetrcApiResponse
    {
        $this->route = '/plantbatches/v1/changegrowthphase';
        $this->method = 'POST';
        $response = $this->executeAction($planting);
       //Log::info("MetricApi@changeGrowthPhase (response)", ['$response'=>$response]);
        return $response;
    }

    /**
     * Destroy a plant batch
     *
     * @see https://api-ca.metrc.com/Documentation/#PlantBatches.post_plantbatches_v1_destroy
     * @param PlantBatchDestruction $batchDestruction
     * @return MetrcApiResponse
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function destroyPlantBatch(PlantBatchDestruction $batchDestruction): MetrcApiResponse
    {
        $this->route = '/plantbatches/v1/destroy';
        $this->method = 'POST';
        $response = $this->executeAction($batchDestruction);
        return $response;
    }

    /**
     * Get information about a plant
     *
     * @see https://api-ca.metrc.com/Documentation/#Plants.get_plants_v1_{id}
     * @param int $id
     * @return Room|null
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function getPlant(string $PlantTag): MetrcApiResponse //?Plant      //SBS Changed //int $id): ?Plant
    {
        $this->route = '/plants/v1/' . $PlantTag; //SBS Changed from $id
        $response = $this->executeAction();
        return $response; //$this->mapResponseToObject($response, Plant::class);
    }

    /**
     * Get an array of plants
     *
     * @see https://api-ca.metrc.com/Documentation/#Plants.get_plants_v1_vegetative
     * @param string $type type filter (vegetative|flowering|onhold|inactive)
     * @param \DateTimeInterface|null $startDate
     * @param \DateTimeInterface|null $stopDate
     * @return array
     * @throws InvalidMetrcResponseException
     */
    public function getPlants($type = 'vegetative', \DateTimeInterface $startDate = null, \DateTimeInterface $stopDate = null): ?array
    {
        $this->route = '/plants/v1/' . $type;
        if($startDate && $stopDate) {
            $this->queryParams['lastModifiedStart'] = $startDate->format(\DateTime::ISO8601);
            $this->queryParams['lastModifiedEnd']   = $stopDate->format(\DateTime::ISO8601);
        }
        $response = $this->executeAction();
        return $this->mapResponseToObjectArray($response, Plant::class);
    }

    /**
     * Get an array of plant waste reasons
     *
     * @see https://api-ca.metrc.com/Documentation/#Plants.get_plants_v1_waste_reasons
     * @return array|null
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function getPlantWasteReasons(): ?array
    {
        $this->route = '/plants/v1/waste/reasons';
        $response = $this->executeAction();
        return $response->getResponse();
    }

    public function getHarvestWasteTypes(): ?array
    {
        $this->route = '/harvests/v1/waste/types';
        $response = $this->executeAction();
        return $response->getResponse();
    }

    /**
     * Destroy a plant
     *
     * @see https://api-ca.metrc.com/Documentation/#Plants.post_plants_v1_destroyplants
     * @param Plant $plant
     * @return MetrcApiResponse
     * @throws InvalidMetrcResponseException
     */
    public function destroyPlant(Plant $plant): MetrcApiResponse
    {
        $this->route = '/plants/v1/destroyplants';
        $this->method = 'POST';        
        $response = $this->executeAction($plant);
       //Log::info("MetricApi@destroyPlant (response)", ['$response'=>$response]);
        return $response;
    }

    /**
     * Move a plant to another room
     *
     * @see https://api-ca.metrc.com/Documentation/#Plants.post_plants_v1_moveplants
     * @param Plant $plant
     * @return MetrcApiResponse
     * @throws InvalidMetrcResponseException
     */
    public function movePlant(Plant $plant): MetrcApiResponse
    {
        $this->route = '/plants/v1/moveplants';
        $this->method = 'POST';
        $response = $this->executeAction($plant);
       //Log::info("MetricApi@movePlant (response)", ['$response'=>$response]);
        return $response;
    }

    public function plantsCreatePlantings(Plant $plant): MetrcApiResponse
    {
        $this->route = '/plants/v1/create/plantings';                        
        $this->method = 'POST';
       //Log::info("MetricApi@plantsCreatePlantings (plant)", ['$plant'=>$plant]);
        $response = $this->executeAction($plant);
       //Log::info("MetricApi@plantsCreatePlantings (response)", ['$response'=>$response]);
        return $response;
    }

    public function plantsCreatePlantBatchPackages(Plant $plant): MetrcApiResponse
    {
        $this->route = '/plants/v1/create/plantbatch/packages';                        
        $this->method = 'POST';
        //Log::info("MetricApi@plantsCreatePlantBatchPackages (plant)", ['$plant'=>$plant]);
        $response = $this->executeAction($plant);
       //Log::info("MetricApi@plantsCreatePlantBatchPackages (response)", ['$response'=>$response]);
        return $response;
    }

    /**
     * Manicure a plant
     *
     * @see https://api-ca.metrc.com/Documentation/#Plants.post_plants_v1_manicureplants
     * @param PlantHarvest $plant
     * @return MetrcApiResponse
     * @throws InvalidMetrcResponseException
     */
    public function manicurePlant(PlantHarvest $plant): MetrcApiResponse 
    {
        $this->route = '/plants/v1/manicureplants';
        $this->method = 'POST';
        $response = $this->executeAction($plant);   
       //Log::info("MetricApi@manicurePlant (response)", ['$response'=>$response]);
        return $response;
    }

    /**
     * Harvest a plant
     *
     * @see https://api-ca.metrc.com/Documentation/#Plants.post_plants_v1_harvestplants
     * @param PlantHarvest $plant
     * @return MetrcApiResponse
     * @throws InvalidMetrcResponseException
     */
    public function harvestPlant(PlantHarvest $plant): MetrcApiResponse
    {
        $this->route = '/plants/v1/harvestplants';
        $this->method = 'POST';
        $response = $this->executeAction($plant);
       //Log::info("MetricApi@harvestPlant (response)", ['$response'=>$response]);
        return $response;
    }

    /**
     * Get information about a room
     *
     * @see https://api-ca.metrc.com/Documentation/#Rooms.get_rooms_v1_{id}
     * @param int $id
     * @return Room|null
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function getRoom(int $id): ?Room
    {
        $this->route = '/rooms/v1/' . $id;
        $response = $this->executeAction();
        return $this->mapResponseToObject($response, Room::class);
    }

    /**
     * Get an array of rooms
     *
     * @see https://api-ca.metrc.com/Documentation/#Rooms.get_rooms_v1_active
     * @param string $type type filter (active|onhold|inactive)
     * @return array
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function getRooms($type = 'active'): ?array
    {
        $this->route = '/rooms/v1/' . $type;
        $response = $this->executeAction();
        return $this->mapResponseToObjectArray($response, Room::class);
    }

    /**
     * Create a room
     *
     * @see https://api-ca.metrc.com/Documentation/#Rooms.post_rooms_v1_create
     * @param Room $room
     * @return MetrcApiResponse
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function createRoom(Room $room): MetrcApiResponse
    {
        $this->route = '/rooms/v1/create';
        $this->method = 'POST';
        $response = $this->executeAction($room);
        return $response;
    }

    /**
     * Update a room
     *
     * @see https://api-ca.metrc.com/Documentation/#Rooms.post_rooms_v1_update
     * @param Room $room
     * @return MetrcApiResponse
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function updateRoom(Room $room): MetrcApiResponse
    {
        $this->route = '/rooms/v1/update';
        $this->method = 'POST';
        $response = $this->executeAction($room);
        return $response;
    }

    /**
     * Delete a room
     *
     * @see https://api-ca.metrc.com/Documentation/#Rooms.delete_rooms_v1_{id}
     * @param int|null $id
     * @return MetrcApiResponse
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function deleteRoom(?int $id): MetrcApiResponse
    {
        $this->route = '/rooms/v1/' . $id;
        $this->method = 'DELETE';
        $response = $this->executeAction();
        return $response;
    }

    /**
     * Get information about a strain
     *
     * @see https://api-ca.metrc.com/Documentation/#Strains.get_strains_v1_{id}
     * @param $id
     * @return array
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function getStrain($id)//: ?array
    {
        $this->route = '/strains/v1/' . $id;
        //Log::info("MetricApi@getStrain (request sent)", ['$id'=>$id, '$this'=>$this->route]);
        $response = $this->executeAction();
       //Log::info("MetricApi@getStrain (response)", ['$response'=>$response]);
        return $response;//$this->mapResponseToObject($response, Strain::class);
    }

    /**
     * Get a list of strains
     *
     * @see https://api-ca.metrc.com/Documentation/#Strains.get_strains_v1_active
     * @return array
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function getStrains(): ?array
    {
        $this->route = '/strains/v1/active';
        $response = $this->executeAction();
        return $this->mapResponseToObjectArray($response, Strain::class);
    }

    /**
     * Create a strain
     *
     * @see https://api-ca.metrc.com/Documentation/#Strains.post_strains_v1_create
     * @param Strain $strain
     * @return MetrcApiResponse
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function createStrain(Strain $strain): MetrcApiResponse
    {
        $this->route = '/strains/v1/create';
        $this->method = 'POST';
        //Log::info("MetricApi@createStrain (request sent)", ['$strain'=>$strain]);
        $response = $this->executeAction($strain);
       //Log::info("MetricApi@createStrain (response)", ['$strain'=>$strain]);
        return $response;
    }

    /**
     * Update a strain
     *
     * @see https://api-ca.metrc.com/Documentation/#Strains.post_strains_v1_update
     * @param strain $strain
     * @return MetrcApiResponse
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function updateStrain(Strain $strain): MetrcApiResponse
    {
        $this->route = '/strains/v1/update';
        $this->method = 'POST';
        //Log::info("MetricApi@updateStrain (request sent)", ['$strain'=>$strain]);
        $response = $this->executeAction($strain);
       //Log::info("MetricApi@updatteStrain (response)", ['$response'=>$response]);
        return $response;
    }

    /**
     * Delete a strain
     *
     * @see https://api-ca.metrc.com/Documentation/#Strains.delete_strains_v1_{id}
     * @param int|null $id
     * @return MetrcApiResponse
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function deleteStrain(?int $id): MetrcApiResponse
    {
        $this->route = '/strains/v1/' . $id;
        $this->method = 'DELETE';
        $response = $this->executeAction();
        return $response;
    }

    /**
     * Get information about a sales receipt
     *
     * @see https://api-ca.metrc.com/Documentation/#Sales.get_sales_v1_receipts_{id}
     * @param $id
     * @return array
     * @throws InvalidMetrcResponseException
     */
    public function getSalesReceipt($id): ?array
    {
        $this->route = '/sales/v1/receipts/' . $id;
        $response = $this->executeAction();
        return $this->mapResponseToObject($response, SalesReceipt::class);
    }

    /**
     * Get an array of receipts between two date ranges
     *
     * @see https://api-ca.metrc.com/Documentation/#Sales.get_sales_v1_receipts
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $stopDate
     * @return array
     * @throws InvalidMetrcResponseException
     */
    public function getSalesReceipts(\DateTimeInterface $startDate, \DateTimeInterface $stopDate): ?array
    {
        $this->route = '/sales/v1/receipts';
        $this->queryParams['lastModifiedStart'] = $startDate->format(\DateTime::ISO8601);
        $this->queryParams['lastModifiedEnd']   = $stopDate->format(\DateTime::ISO8601);
        $response = $this->executeAction();
        return $this->mapResponseToObjectArray($response, SalesReceipt::class);
    }

    /**
     * Create a sales receipt
     *
     * @see https://api-ca.metrc.com/Documentation/#Sales.post_sales_v1_receipts
     * @param SalesReceipt $receipt
     * @return MetrcApiResponse
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function createSalesReceipt(SalesReceipt $receipt): MetrcApiResponse
    {
        $this->route = '/sales/v1/receipts';
        $this->method = 'POST';
        $response = $this->executeAction($receipt);
        return $response;
    }

    /**
     * Update a sales receipt
     *
     * @see https://api-ca.metrc.com/Documentation/#Sales.put_sales_v1_receipts
     * @param SalesReceipt $receipt
     * @return MetrcApiResponse
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function updateSalesReceipt(SalesReceipt $receipt): MetrcApiResponse
    {
        $this->route = '/sales/v1/receipts';
        $this->method = 'PUT';
        $response = $this->executeAction($receipt);
        return $response;
    }

    /**
     * Delete a sales receipt
     *
     * @see https://api-ca.metrc.com/Documentation/#Sales.delete_sales_v1_receipts_{id}
     * @param $id
     * @return MetrcApiResponse
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function deleteSalesReceipt($id): MetrcApiResponse
    {
        $this->route = '/sales/v1/receipts/' . $id;
        $this->method = 'DELETE';
        $response = $this->executeAction();
        return $response;
    }

    /**
     * Get an array of lab test types
     *
     * @see https://api-ca.metrc.com/Documentation/#LabTests.get_labtests_v1_types
     * @return array|null
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function getLabTestTypes(): ?array
    {
        $this->route = '/labtests/v1/types';
        $response = $this->executeAction();
        return $response->getResponse();
    }

    /**
     * Record a lab test result
     *
     * @see https://api-ca.metrc.com/Documentation/#LabTests.post_labtests_v1_record
     * @param LabTest $labTest
     * @return MetrcApiResponse
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function createLabTest(LabTest $labTest): MetrcApiResponse
    {
        $this->route = '/labtests/v1/record';
        $this->method = 'POST';
        $response = $this->executeAction($labTest);
        return $response;
    }

    /**
     * Get an array of transfers
     *
     * @see https://api-ca.metrc.com/Documentation/#Transfers.get_transfers_v1_incoming
     * @param string $type type filter (incoming|outgoing|rejected)
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $stopDate
     * @return array
     * @throws InvalidMetrcResponseException
     */
    public function getTransfers($type = 'incoming', \DateTimeInterface $startDate = null, \DateTimeInterface $stopDate = null): ?array
    {
        $this->route = '/transfers/v1/' . $type;
        if($startDate && $stopDate) {
            $this->queryParams['lastModifiedStart'] = $startDate->format(\DateTime::ISO8601);
            $this->queryParams['lastModifiedEnd'] = $stopDate->format(\DateTime::ISO8601);
        }
        $response = $this->executeAction();
       //Log::info("MetricApi@getTransfers (response)", ['$response'=>$response]);
        return $this->mapResponseToObjectArray($response, Transfer::class);
    }

    //SBS added
    public function findTransfer($id): MetrcApiResponse
    {
        $this->route = '/transfers/v1/' . $id . '/deliveries/';
        //$this->route = '/transfers/v1/delivery/' . $id . '/packages/wholesale';        
        $this->method = 'GET';
        $response = $this->executeAction();
       //Log::info("MetricApi@findTransfers (response)", ['$response'=>$response]);
        return $response;
    }

    /**
     * Get an array of lab test types
     *
     * @see https://api-ca.metrc.com/Documentation/#UnitsOfMeasure.get_unitsofmeasure_v1_active
     * @return array|null
     * @throws \Exception|InvalidMetrcResponseException
     */
    public function getUnitsOfMeasure(): ?array
    {
        $this->route = '/unitsofmeasure/v1/active';
        $response = $this->executeAction();
        return $response->getResponse();
    }
}
