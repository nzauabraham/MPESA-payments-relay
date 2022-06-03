<?php

namespace App\Services\Emalify\src\Support;

class NetworkMapper
{
    public static function getNetwork($msisdn)
    {
        $supportedNetworks = [
            /* Safaricom KE Suffix */
            'safaricom' => "/^\+?(?:254|0)(7(?:[0129]\d{7}|5[789]\d{6}|4[01234568]\d{6}|6[89]\d{6})|1(?:1[01]\d{6}))$/",
            /* Airtel KE Suffix */
            'airtel' => "/^\+?(?:254|0)(7(?:[38]\d{7}|5[0123456]\d{6})|1(?:0[1]\d{6}))$/",
            /* Telkom KE Suffix */
            'telkom' => "/^\+?(?:254|0)7(?:[7]\d{7})$/",
        ];
        foreach ($supportedNetworks as $network => $regex) {
            if (preg_match($regex, $msisdn)) {
                return true;
            }
        }
        return false;
    }
}
