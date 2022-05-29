<?php

namespace App\Enum;

abstract class BinancePeriodVO
{
    protected string $value;

    public function getValue()
    {
        return $this->value;
    }
    public function getCode()
    {
        return $this->code;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}