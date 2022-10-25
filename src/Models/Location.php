<?php

namespace MetrcApi\Models;

class Location extends ApiObject
{
    public $id = null;
    public $name;
    public $LocationTypeId;
    public $LocationTypeName;
    public $ForPlantBatches;
    public $ForPlants;
    public $ForPackages;    
    
    public function getId(): int
    {
        return $this->id;
    }
    
    public function setId(int $id): void
    {
        $this->id = $id;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function setName(string $name): void
    {
        $this->name = $name;
    }
    /*
    public function getLocationTypeId(): string
    {
        return $this->LocationTypeId;
    }
    
    public function setLocationTypeId(string $LocationTypeId): void
    {
        $this->LocationTypeId = $LocationTypeId;
    }
    */
    public function getLocationTypeName(): string
    {
        return $this->LocationTypeName;
    }
    
    public function setLocationTypeName(string $LocationTypeName): void
    {
        $this->LocationTypeName = $LocationTypeName;
    }
    
    /*
    public function getForPlantBatches(): ?float
    {
        return $this->ForPlantBatches;
    }
    
    public function setForPlantBatches(?float $ForPlantBatches): void
    {
        $this->ForPlantBatches = $ForPlantBatches;
    }
    
    public function getForPlants(): ?float
    {
        return $this->ForPlants;
    }

    public function setForPlants(?float $ForPlants): void
    {
        $this->ForPlants = $ForPlants;
    }

    public function getForPackages(): ?float
    {
        return $this->ForPackages;
    }
    
    public function setForPackages(?float $ForPackages): void
    {
        $this->ForPackages = $ForPackages;
    }
    */
    public function toArray()
    {
        return [
            'Id' => $this->id,
            'Name' => $this->name,
            "LocationTypeId" => $this->LocationTypeId,
            "LocationTypeName" => $this->LocationTypeName,
            "ForPlantBatches" => $this->ForPlantBatches,
            "ForPlants" => $this->ForPlants,
            "ForPackages" => $this->ForPackages        
        ];
    }
}