<?php

namespace App\Enum;

enum ModelForecastStep: string
{
    case Month = 'month';
    case Quarter = 'quarter';
    case Year = 'year';
}
