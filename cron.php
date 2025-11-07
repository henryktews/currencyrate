<?php

include dirname(__FILE__) . '/../../config/config.inc.php';
include dirname(__FILE__) . '/../../init.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

$module = Module::getInstanceByName('currencyrate');
if ($module && $module->active) {
    /** @var CurrencyRate\Service\CurrencyRateService $currencyService */
    $currencyService = $module->get('currencyrate.service.currency_rate_service');

    if ($currencyService->updateCurrencyRates()) {
        echo "Currency rates updated successfully\n";
    } else {
        echo "Failed to update currency rates\n";
    }
} else {
    echo "CurrencyRate module not found or not active\n";
}