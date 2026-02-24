<?php

namespace App\Service;

use App\Entity\Main;
use App\Repository\MainRepository;

readonly class MainService
{
    public function __construct(
        private MainRepository $mainRepository,
    ) {
    }

    public function getMainData(): ?Main
    {
        return $this->mainRepository->findMainPageData();
    }
}
