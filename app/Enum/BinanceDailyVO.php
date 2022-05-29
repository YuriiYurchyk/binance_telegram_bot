<?php

namespace App\Enum;

final class BinanceDailyVO extends BinancePeriodVO
{
    protected string $value = 'daily';

    protected int $code = 1;
}