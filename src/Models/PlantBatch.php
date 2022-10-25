<?php

namespace MetrcApi\Models;

class PlantBatch extends ApiObject
{

    public function getPlantBatch() {  return $this->PlantBatch;  }
    //public function getGroupName() {  return $this->GroupName;  }
    public function getCount() {  return $this->Count;  }
    public function getLocation() {  return $this->Location;  }
    //public function getStrain() {  return $this->Strain;  }
    //public function getPatientLicenseNumber() {  return $this->PatientLicenseNumber;  }
    
    public function setPlantBatch($PlantBatch): void {  $this->PlantBatch = $PlantBatch;   }
    //public function setGroupName($GroupName): void {  $this->GroupName = $GroupName;   }
    public function setCount($Count): void {  $this->Count = $Count;   }
    public function setLocation($Location): void {  $this->Location = $Location;   }
    //public function setStrain($Strain): void {  $this->Strain = $Strain;   }
    //public function setPatientLicenseNumber($PatientLicenseNumber): void {  $this->PatientLicenseNumber = $PatientLicenseNumber;   }

    public function getId() {  return $this->Id;  }
    public function getItem() {  return $this->Item;  }
    public function getTag() {  return $this->Tag;  }
    public function getNote() {  return $this->Note;  }
    public function getIsTradeSample() {  return $this->IsTradeSample;  }
    public function getIsDonation() {  return $this->IsDonation;  }

    public function setId($Id): void {  $this->Id = $Id;   }
    public function setItem($Item): void {  $this->Item = $Item;   }
    public function setTag($Tag): void {  $this->Tag = $Tag;   }
    public function setNote($Note): void {  $this->Note = $Note;   }
    public function setIsTradeSample($IsTradeSample): void {  $this->IsTradeSample = $IsTradeSample;   }
    public function setIsDonation($IsDonation): void {  $this->IsDonation = $IsDonation;   }
      
    public function getActualDate(): \DateTimeInterface
    {
        return $this->actualDate;
    }

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
    
    public function toArray()
    {
        return [
            'PlantBatch'=> $this->getPlantBatch(),
            //'GroupName'=> $this->getGroupName(),
            'Count'=> $this->getCount(),
            'Location'=> $this->getLocation(),
            //'Strain'=> $this->getStrain(),
            //'PatientLicenseNumber'=> $this->getPatientLicenseNumber(),
            'Id'=> $this->getId(),
            'Item'=> $this->getItem(),
            'Tag'=> $this->getTag(),
            'Note'=> $this->getNote(),
            'IsTradeSample'=> $this->getIsTradeSample(),
            'IsDonation'=> $this->getIsDonation(),
            'ActualDate' => $this->getActualDate()->format('Y-m-d')
        ];
    }


}