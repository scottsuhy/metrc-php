<?php

namespace MetrcApi\Models;

use MetrcApi\Exception\InvalidMetrcResponseException;

class Plant extends ApiObject
{
    /**
     * @var null|int
     */
    public $id = null;

    /**
     * @var string
     */
    public $label;

    /**
     * @var string|null
     */
    public $room;

    /**
     * @var \DateTimeInterface
     */
    public $actualDate;

    /**
     * @var string|null
     */
    public $reasonNote;

    //SBS Added
    public  $LocationName;
    public  $PlantBatchName;
    public  $PlantBatchType;
    public  $PlantCount;
    public  $StrainName;
    public  $PatientLicenseNumber;
    public  $PackageTag;
    public  $Item;
    public  $Note;
    public  $IsTradeSample;
    public  $IsDonation;
    public  $Count;
    public  $WasteMethodName;
    public  $WasteMaterialMixed;
    public  $WasteWeight;
    public  $WasteUnitOfMeasureName;
    public  $WasteReasonName;
    public  $ReasonNote;
    public  $Weight;
    public  $UnitOfWeight;
    public  $DryingLocation;

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
    public function getLabel(): string
    {
        //SBS added ?? $this->label
        return $this->PlantLabel ?? $this->Label;
    }

    /**
     * @param string $label
     */
    public function setLabel(string $label): void
    {
        $this->PlantLabel = $label;
        //SBS added
        $this->Label = $label;        
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

    //SBS added
    public function setLocation(?string $LocationName): void
    {  $this->LocationName = $LocationName;
       $this->Location = $LocationName;
    }
    public function setPlantBatchName(?string $PlantBatchName): void
    {  $this->PlantBatchName = $PlantBatchName;  }
    public function setPlantBatchType(?string $PlantBatchType): void
    {  $this->PlantBatchType = $PlantBatchType;  }
    public function setPlantCount(?int $PlantCount): void
    {  $this->PlantCount = $PlantCount;  }
    public function setStrainName(?string $StrainName): void
    {  $this->StrainName = $StrainName;  }    
    public function setPatientLicenseNumber(?string $PatientLicenseNumber): void
    {  $this->PatientLicenseNumber = $PatientLicenseNumber;  }
    public function setPackageTag(?string $PackageTag): void
    {  $this->PackageTag = $PackageTag;  }
    public function setItem(?string $Item): void
    {  $this->Item = $Item;  }
    public function setNote(?string $Note): void
    {  $this->Note = $Note;  }    
    public function setIsTradeSample(?bool $IsTradeSample): void
    {  $this->IsTradeSample = $IsTradeSample;  }
    public function setIsDonation(?bool $IsDonation): void
    {  $this->IsDonation = $IsDonation;  }
    public function setCount(?int $Count): void
    {  $this->Count = $Count;  }
    public function setWasteMethodName(?string $WasteMethodName): void
    {  $this->WasteMethodName = $WasteMethodName;  }    
    public function setWasteMaterialMixed(?string $WasteMaterialMixed): void
    {  $this->WasteMaterialMixed = $WasteMaterialMixed;  }    
    public function setWasteWeight(?string $WasteWeight): void
    {  $this->WasteWeight = $WasteWeight;  }    
    public function setWasteUnitOfMeasureName(?string $WasteUnitOfMeasureName): void
    {  $this->WasteUnitOfMeasureName = $WasteUnitOfMeasureName;  }    
    public function setWasteReasonName(?string $WasteReasonName): void
    {  $this->WasteReasonName = $WasteReasonName;  }      
    public function setWeight(?float $Weight): void
    {  $this->Weight = $Weight;  }      
    public function setUnitOfWeight(?string $UnitOfWeight): void
    {  $this->UnitOfWeight = $UnitOfWeight;  }      
    public function setDryingLocation(?string $DryingLocation): void
    {  $this->DryingLocation = $DryingLocation;  } 
    
    public function getLocation(): ?string
    {  return $this->LocationName;     }
    public function getPlantBatchName(): ?string
    {  return $this->PlantBatchName;  }
    public function getPlantBatchType(): ?string
    {  return $this->PlantBatchType;  }
    public function getPlantCount(): ?int
    {  return $this->PlantCount;  }
    public function getStrainName(): ?string
    {  return $this->StrainName;  }    
    public function getPatientLicenseNumber(): ?string
    {  return $this->PatientLicenseNumber;  }
    public function getPackageTag(): ?string
    {  return $this->PackageTag;  }
    public function getItem(): ?string
    {  return $this->Item;  }
    public function getNote(): ?string
    {  return $this->Note;  }    
    public function getIsTradeSample(): ?bool
    {  return $this->IsTradeSample;  }
    public function getIsDonation(): ?bool
    {  return $this->IsDonation;  }
    public function getCount(): ?int
    {  return $this->Count;  }
    public function getWasteMethodName(): ?string
    {  return $this->WasteMethodName;  }    
    public function getWasteMaterialMixed(): ?string
    {  return $this->WasteMaterialMixed;  }    
    public function getWasteWeight(): ?string
    {  return $this->WasteWeight;  }    
    public function getWasteUnitOfMeasureName(): ?string
    {  return $this->WasteUnitOfMeasureName;  }    
    public function getWasteReasonName(): ?string
    {  return $this->WasteReasonName;  }    
    public function getWeight(): ?float
    {  return $this->Weight;  }      
    public function getUnitOfWeight(): ?string
    {  return $this->UnitOfWeight;  }      
    public function getDryingLocation(): ?string
    {  return $this->DryingLocation;  } 

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
     * @return string|null
     */
    public function getReasonNote(): ?string
    {
        return $this->reasonNote;
    }

    /**
     * @param string|null $reasonNote
     */
    public function setReasonNote(?string $reasonNote): void
    {
        $this->reasonNote = $reasonNote;
    }

    public function toArray()
    {
        return [
            'Id' => $this->getId(),
            'Label' => $this->getLabel(),
            'PlantLabel' => $this->getLabel(),
            'ReasonNote' => $this->getReasonNote(),
            'Room' => $this->getRoom(),
            'ActualDate' => $this->getActualDate()->format('Y-m-d'),
            //SBS added
            'LocationName' => $this->getLocation(),
            'Location' => $this->getLocation(),
            'PlantBatchName' => $this->getPlantBatchName(),
            'PlantBatchType' => $this->getPlantBatchType(),
            'PlantCount' => $this->getPlantCount(),
            'StrainName' => $this->getStrainName(),
            'PatientLicenseNumber' => $this->getPatientLicenseNumber(),
            'PackageTag' => $this->getPackageTag(),
            'Item' => $this->getItem(),
            'Note' => $this->getNote(),
            'IsTradeSample' => $this->getIsTradeSample(),
            'IsDonation' => $this->getIsDonation(),
            'Count' => $this->getCount(),
            'WasteMethodName' => $this->getWasteMethodName(),
            'WasteMaterialMixed' => $this->getWasteMaterialMixed(),
            'WasteWeight' => $this->getWasteWeight(),
            'WasteUnitOfMeasureName' => $this->getWasteUnitOfMeasureName(),
            'WasteReasonName' => $this->getWasteReasonName(),
            'Weight' => $this->getWeight(),
            'UnitOfWeight' => $this->getUnitOfWeight(),
            'DryingLocation' => $this->getDryingLocation()            
        ];
    }
}