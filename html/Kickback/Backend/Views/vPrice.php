<?php

declare(strict_types=1);

namespace Kickback\Backend\Views;

use Exception;

use \Kickback\Backend\Models\Enums\CurrencyCode;
use \Kickback\Backend\Models\ForeignRecordId;

use \Kickback\Backend\Views\vDecimal;

use \Kickback\Backend\Controllers\CurrencyConverter;

class vPrice
{
    public int $smallCurrencyUnit;
    public ?ForeignRecordId $currencyItemId;

    public function __construct(mixed $smallCurrencyUnit, ?vRecordId $currencyItemId = null)
    {
        $this->smallCurrencyUnit = $smallCurrencyUnit;
        $this->currencyItemId =  is_null($currencyItemId) ? null : new ForeignRecordId($currencyItemId->ctime, $currencyItemId->crand);
    }

    public function __toString()
    {
        return strval($this->smallCurrencyUnit);
    }

    public function returnPriceIn(CurrencyCode $currencyCode = CurrencyCode::ADA) : vDecimal
    {
        switch($currencyCode)
        {
            case CurrencyCode::ADA :
                $amount = new vDecimal($this->smallCurrencyUnit, 15, 6);
                return $amount;
            break;
            case CurrencyCode::USD :
                $amount = vPrice::convertFromADA($this->returnPriceIn(CurrencyCode::ADA), CurrencyCode::USD);
                return $amount;
        }
    }

    public static function adaStringToLovelace(string $amount)
    {
        $lovelaceString = str_replace('.', '', $amount);
        $lovelace = intval($lovelaceString);

        return $lovelace;
    }

    public function returnPriceWithSymbol(CurrencyCode $currencyCode = CurrencyCode::ADA)
    {
        switch($currencyCode)
        {
            case CurrencyCode::ADA :
                (string)$amount = $this->returnPriceIn(CurrencyCode::ADA);
                return "$".$amount." ADA";
            case CurrencyCode::USD : 
                (string)$amount = $this->returnPriceIn(CurrencyCode::USD);
                return "$".$amount." USD";

        }
    }

    public static function convertFromADA(vDecimal $amount, CurrencyCode $code) : vDecimal
    {
        switch($code)
        {
            case CurrencyCode::ADA :
                return $amount;
            case CurrencyCode::USD :
                $rate = new vDecimal(3434,0,4);
                $amount = $amount->mul($rate);
                $amount = $amount->round(2);
                return $amount;
            default :
                throw new Exception("CurrencyCode not recognized : $code");
        }
    }

}

?>
