<?php

namespace MetrcApi\Models;

use MetrcApi\Exception\InvalidMetrcResponseException;

class PackageCreatePlantings extends ApiObject
{    

    /**
     * @var string
     */
    public  $label;    
    public  $LocationName;
    public  $PlantBatchName;
    public  $PlantBatchType;
    public  $PlantCount;
    public  $StrainName;
    public  $PatientLicenseNumber;
    public  $plantedDate;
    public  $UnpackagedDate;
    public  $PackageAdjustmentAmount;
    public  $PackageAdjustmentUnitOfMeasureName;

    public function __construct()
    {
        //$this->PlantedDate = new \DateTime();
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {        
        return $this->PackageLabel ?? $this->Label;
    }

    /**
     * @param string $label
     */
    public function setPackageLabel(string $packagetag): void
    {
        $this->PackageLabel = $packagetag;        
        $this->Label = $packagetag;        
    }

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
    public function setPackageAdjustmentAmount(?string $PackageAdjustmentAmount): void
    {  $this->PackageAdjustmentAmount = $PackageAdjustmentAmount;  }
    public function setPackageAdjustmentUnitOfMeasureName(?string $PackageAdjustmentUnitOfMeasureName): void
    {  $this->PackageAdjustmentUnitOfMeasureName = $PackageAdjustmentUnitOfMeasureName;  }


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
    public function getPackageAdjustmentAmount(): ?float
    {  return $this->PackageAdjustmentAmount;  }
    public function getPackageAdjustmentUnitOfMeasureName(): ?string
    {  return $this->PackageAdjustmentUnitOfMeasureName;  }

    /**
     * @return \DateTimeInterface
     */
    public function getPlantedDate(): \DateTimeInterface
    {
        return $this->PlantedDate;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getUnpackagedDate(): \DateTimeInterface
    {
        return $this->UnpackagedDate;
    }


    /**
     * @param \DateTimeInterface|string $PlantedDate
     * @throws InvalidMetrcResponseException
     */
    public function setPlantedDate($PlantedDate): void
    {
        if(is_string($PlantedDate)) {
            $this->PlantedDate = new \DateTime($PlantedDate);
        } elseif($PlantedDate instanceof \DateTime) {
            $this->PlantedDate = $PlantedDate;
        } else {
            throw new InvalidMetrcResponseException('Unexpected Date Format: '.$PlantedDate);
        }
    }

        /**
     * @param \DateTimeInterface|string $Date
     * @throws InvalidMetrcResponseException
     */
    public function setUnpackagedDate($UnpackagedDate): void
    {
        if(is_string($UnpackagedDate)) {
            $this->UnpackagedDate = new \DateTime($UnpackagedDate);
        } elseif($UnpackagedDate instanceof \DateTime) {
            $this->UnpackagedDate = $UnpackagedDate;
        } else {
            throw new InvalidMetrcResponseException('Unexpected Date Format: '.$UnpackagedDate);
        }
    }

    public function toArray()
    {
        
        return array([            
            'PackageLabel' => $this->getPackageLabel(),
            'PackageAdjustmentAmount' => $this->getPackageAdjustmentAmount(),
            'PackageAdjustmentUnitOfMeasureName' => $this->getPackageAdjustmentUnitOfMeasureName(),
            'PlantBatchName' => $this->getPlantBatchName(),
            'PlantBatchType' => $this->getPlantBatchType(),
            'PlantCount' => $this->getPlantCount(),
            'LocationName' => $this->getLocation(),
            'StrainName' => $this->getStrainName(),
            'PatientLicenseNumber' => $this->getPatientLicenseNumber(),
            'PlantedDate' => $this->getPlantedDate()->format('Y-m-d'),
            'UnpackagedDate' => $this->getUnpackagedDate()->format('Y-m-d'),            
        ]);
    }
}