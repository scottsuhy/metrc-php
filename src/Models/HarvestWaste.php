<?php

namespace MetrcApi\Models;

use MetrcApi\Exception\InvalidMetrcResponseException;

class HarvestWaste extends ApiObject
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $unitOfWeight;

    /**
     * @var float
     */
    public $wasteWeight;

    public $wasteType; //SBS Added

    /**
     * @var \DateTimeInterface
     */
    public $actualDate;

    public function __construct()
    {
        $this->actualDate = new \DateTime();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getUnitOfWeight(): string
    {
        return $this->unitOfWeight;
    }

    /**
     * @param string $unitOfWeight
     */
    public function setUnitOfWeight(string $unitOfWeight): void
    {
        $this->unitOfWeight = $unitOfWeight;
    }

    /**
     * @return float
     */
    public function getWasteWeight(): float
    {
        return $this->wasteWeight;
    }

    //SBS Added
    public function getWasteType(): string
    {
        return $this->wasteType;
    }

    /**
     * @param float $wasteWeight
     */
    public function setWasteWeight(float $wasteWeight): void
    {
        $this->wasteWeight = $wasteWeight;
    }

    //SBS Added
    public function setWasteType(string $wasteType): void
    {
        $this->wasteType = $wasteType;
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

    public function toArray()
    {
        return array([
            'Id' => $this->getId(),
            'UnitOfWeight' => $this->getUnitOfWeight(),
            'WasteWeight' => $this->getWasteWeight(),
            'WasteType' => $this->getWasteType(), //SBS Added
            'ActualDate' => $this->getActualDate()->format('Y-m-d')
        ]);
    }
}