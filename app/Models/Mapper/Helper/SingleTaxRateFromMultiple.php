<?php

namespace App\Models\Mapper\Helper;

trait SingleTaxRateFromMultiple
{
    protected function getTaxRate(array $taxRates)
    {
        $uniqueTaxRates = array_unique($taxRates);
        if (count($uniqueTaxRates) > 1) {
            throw new \Exception('Multiple tax rates are not supported.');
        } elseif (count($uniqueTaxRates) === 1) {
            return reset($taxRates);
        } else {
            return 0;
        }
    }
}