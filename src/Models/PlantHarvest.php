<?php

namespace MetrcApi\Models;

use MetrcApi\Exception\InvalidMetrcResponseException;

use Illuminate\Support\Facades\Log;

class PlantHarvest extends ApiObject
{
    /**
     * @var null|int
     */
    public $id = null;

    /**
     * @var string
     */
    public $plant;

    /**
     * @var string|null
     */
    public $room;

    /**
     * @var string|null
     */
    public $patientLicenseNumber;

    /**
     * @var \DateTimeInterface
     */
    public $actualDate;

    /**
     * @var float
     */
    public $weight;

    /**
     * @var string|null
     */
    public $unitOfWeight;

    /**
     * @var string|null
     */
    public $harvestName;

    //SBS Added
    public $DryingLocation;

    public function __construct()
    {
        $this->actualDate = new \DateTime();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getPlant(): string
    {
        return $this->plant;
    }

    /**
     * @param string $plant
     */
    public function setPlant(string $plant): void
    {
        $this->plant = $plant;
    }

    /**
     * @return string|null
     */
    public function getPatientLicenseNumber(): ?string
    {
        return $this->patientLicenseNumber;
    }

    /**
     * @param string|null $patientLicenseNumber
     */
    public function setPatientLicenseNumber(?string $patientLicenseNumber): void
    {
        $this->patientLicenseNumber = $patientLicenseNumber;
    }

    /**
     * @return string
     */
    public function getRoom(): ?string
    {
        return $this->room;
    }

    /**
     * @param string $room
     */
    public function setRoom(?string $room): void
    {
        $this->room = $room;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getActualDate(): \DateTimeInterface
    {
        return $this->actualDate;
    }

    /**
     * @param \DateTimeInterface|string $actualDate
     * @throws InvalidMetrcResponseException
     */
    public function setActualDate($actualDate): void
    {
        if(is_string($actualDate)) {
            $this->actualDate = new \DateTime($actualDate);
        } elseif($actualDate instanceof \DateTime) {
            $this->actualDate = $actualDate;
        } else {
            throw new InvalidMetrcResponseException('Unexpected Date Format: '.$actualDate);
        }
    }

    /**
     * @return float
     */
    public function getWeight(): float
    {
        return $this->weight;
    }

    /**
     * @param float $weight
     */
    public function setWeight(float $weight): void
    {
        $this->weight = $weight;
    }

    /**
     * @return string|null
     */
    public function getUnitOfWeight(): ?string
    {
        return $this->unitOfWeight;
    }

    /**
     * @param string|null $unitOfWeight
     */
    public function setUnitOfWeight(?string $unitOfWeight): void
    {
        $this->unitOfWeight = $unitOfWeight;
    }

    /**
     * @return string|null
     */
    public function getHarvestName(): ?string
    {
        return $this->harvestName;
    }

    /**
     * @param string|null $harvestName
     */
    public function setHarvestName(?string $harvestName): void
    {
        $this->harvestName = $harvestName;
    }

    //SBS Added
    public function setDryingLocation(?string $DryingLocation): void
    {  $this->DryingLocation = $DryingLocation;  } 
    public function getDryingLocation(): ?string
    {  return $this->DryingLocation;  } 

//changed 12/6/22 -------------------------------------------------------

    public function toArray_old()
    {
        return [
            //'Id' => $this->getId(),
            'Plant' => $this->getPlant(),
            //'DryingRoom' => $this->getRoom(),
            'PatientLicenseNumber' => $this->getPatientLicenseNumber(),
            'HarvestName' => $this->getHarvestName(),
            'Weight' => $this->getWeight(),
            'UnitOfWeight' => $this->getUnitOfWeight(),
            'ActualDate' => $this->getActualDate()->format('Y-m-d'),
            'DryingLocation' => $this->getDryingLocation()    
        ];
    }

//new 12/6/22 -------------------------------------------------------
 
    public function getPlantArray()
    {
        return $this->plantArray;
    }

    public function setPlantArray(array $plantArray)
    {
        $this->plantArray = $plantArray;
    }
 
    public function toArray()                      
    {
        $plantArray = $this->getPlantArray();
        $harvestArray = array();
        $oneplant = array();

        if(env('METRC_API_LOGGING')){
            Log::info("PlantHarvest@toArray", [
                '$plantArray' => $plantArray
            ]);
        }        

        foreach($plantArray as $plant){            
            $oneplant = [
                'Plant' => $plant,            
                'PatientLicenseNumber' => null, //$this->getPatientLicenseNumber(), //changed per metrc support
                'HarvestName' => $this->getHarvestName(),
                'Weight' => $this->getWeight(),
                'UnitOfWeight' => $this->getUnitOfWeight(),
                'ActualDate' => $this->getActualDate()->format('Y-m-d'),
                'DryingLocation' => $this->getDryingLocation()    
            ];
            
            $harvestArray[] = $oneplant;
        }

        if(env('METRC_API_LOGGING')){
            Log::info("PlantHarvest@toArray", [
                '$harvestArray' => $harvestArray
            ]);
        }

        return $harvestArray;
    }
    
    /*public function toArray()                      
    {
        $plantArray = $this->getPlantArray();
        $harvestArray = [];

        if(env('METRC_API_LOGGING')){
            Log::info("PlantHarvest@toArray", [
                '$plantArray' => $plantArray
            ]);
        }

        foreach($plantArray as $plant){
            array_push($harvestArray, [                                      
                'Plant' => $plant,            
                'PatientLicenseNumber' => $this->getPatientLicenseNumber(),
                'HarvestName' => $this->getHarvestName(),
                'Weight' => $this->getWeight(),
                'UnitOfWeight' => $this->getUnitOfWeight(),
                'ActualDate' => $this->getActualDate()->format('Y-m-d'),
                'DryingLocation' => $this->getDryingLocation()    
            ]);
        }

        if(env('METRC_API_LOGGING')){
            Log::info("PlantHarvest@toArray", [
                '$harvestArray' => $harvestArray
            ]);
        }

        return $harvestArray;
    }*/
}