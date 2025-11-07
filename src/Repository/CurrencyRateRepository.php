<?php

namespace CurrencyRate\Repository;

use Db;
use PrestaShopLogger;

class CurrencyRateRepository
{
    public function saveRates(array $rates): bool
    {
        if (empty($rates)) {
            return false;
        }

        $success = true;
        foreach ($rates as $rate) {
            $existing = Db::getInstance()->getValue('
                SELECT id_currency_rate FROM ' . _DB_PREFIX_ . 'currency_rate 
                WHERE currency_code = "' . pSQL($rate['currency_code']) . '" 
                AND date = "' . pSQL($rate['date']) . '"
            ');

            $rateData = [
                'currency_code' => pSQL($rate['currency_code']),
                'rate' => (float) $rate['rate'],
                'date' => pSQL($rate['date']),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if (isset($rate['currency_name'])) {
                $rateData['currency_name'] = pSQL($rate['currency_name']);
            }

            if ($existing) {
                $result = Db::getInstance()->update('currency_rate', $rateData, 'id_currency_rate = ' . (int) $existing);
            } else {
                $rateData['created_at'] = date('Y-m-d H:i:s');
                $result = Db::getInstance()->insert('currency_rate', $rateData);
            }

            if (!$result) {
                $success = false;
                PrestaShopLogger::addLog("CurrencyRate: Failed to save rate for " . $rate['currency_code'], 3);
            }
        }

        return $success;
    }

    public function getLatestRates(): array
    {
        $latestDate = Db::getInstance()->getValue('
            SELECT MAX(date) FROM ' . _DB_PREFIX_ . 'currency_rate
        ');

        if (!$latestDate) {
            return [];
        }

        $rates = Db::getInstance()->executeS('
            SELECT cr.currency_code, cr.rate, cr.date, cr.currency_name
            FROM ' . _DB_PREFIX_ . 'currency_rate cr
            WHERE cr.date = "' . pSQL($latestDate) . '"
            ORDER BY cr.currency_code
        ');

        foreach ($rates as &$rate) {
            if (empty($rate['currency_name'])) {
                $rate['currency_name'] = $rate['currency_code'];
            }
        }

        return $rates;
    }

    public function getPaginatedRates(int $page = 1, int $limit = 10, string $sort = 'date', string $order = 'DESC'): array
    {
        $offset = ($page - 1) * $limit;

        $allowedSortFields = ['currency_code', 'rate', 'date', 'updated_at'];
        $sort = in_array($sort, $allowedSortFields) ? $sort : 'date';
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $result = Db::getInstance()->executeS('
            SELECT currency_code, rate, date, currency_name 
            FROM ' . _DB_PREFIX_ . 'currency_rate 
            ORDER BY ' . pSQL($sort) . ' ' . pSQL($order) . ' 
            LIMIT ' . (int) $offset . ', ' . (int) $limit
        );

        return is_array($result) ? $result : [];
    }

    public function getTotalCount(): int
    {
        $result = Db::getInstance()->getValue('
            SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'currency_rate
        ');

        return (int) $result;
    }

    public function getAvailableCurrencies(): array
    {
        return Db::getInstance()->executeS('
            SELECT DISTINCT currency_code, currency_name
            FROM ' . _DB_PREFIX_ . 'currency_rate 
            ORDER BY currency_code
        ');
    }

    public function getActiveLatestRates(): array
    {
        PrestaShopLogger::addLog("CurrencyRate DEBUG - getActiveLatestRates START", 1);

        $latestDate = Db::getInstance()->getValue('
            SELECT MAX(date) FROM ' . _DB_PREFIX_ . 'currency_rate
            WHERE is_active = 1
        ');

        PrestaShopLogger::addLog("CurrencyRate DEBUG - Latest date with active rates: " . ($latestDate ?: 'NONE'), 1);

        if (!$latestDate) {
            PrestaShopLogger::addLog("CurrencyRate DEBUG - No latest date found", 1);
            return [];
        }

        $rates = Db::getInstance()->executeS('
            SELECT cr.currency_code, cr.rate, cr.date, cr.currency_name
            FROM ' . _DB_PREFIX_ . 'currency_rate cr
            WHERE cr.date = "' . pSQL($latestDate) . '"
            AND cr.is_active = 1
            ORDER BY cr.currency_code
        ');

        PrestaShopLogger::addLog("CurrencyRate DEBUG - Rates from DB count: " . count($rates), 1);

        foreach ($rates as &$rate) {
            if (empty($rate['currency_name'])) {
                $rate['currency_name'] = $rate['currency_code'];
            }
        }

        return $rates;
    }

    public function getAllCurrenciesWithStatus(): array
    {
        return Db::getInstance()->executeS('
            SELECT 
                currency_code,
                currency_name,
                is_active,
                MAX(date) as last_date,
                COUNT(*) as record_count
            FROM ' . _DB_PREFIX_ . 'currency_rate 
            GROUP BY currency_code, currency_name, is_active
            ORDER BY currency_code
        ');
    }

    public function updateCurrencyStatus(string $currencyCode, bool $isActive): bool
    {
        return Db::getInstance()->update(
            'currency_rate',
            ['is_active' => (int)$isActive],
            'currency_code = "' . pSQL($currencyCode) . '"'
        );
    }

    public function getModuleSetting(string $name)
    {
        try {
            $result = Db::getInstance()->getValue('
                SELECT value FROM ' . _DB_PREFIX_ . 'currencyrate_settings
                WHERE name = "' . pSQL($name) . '"
            ');

            PrestaShopLogger::addLog("CurrencyRate DEBUG - DB setting '$name': " . ($result ?: 'NOT FOUND'), 1);

            return $result;
        } catch (\Exception $e) {
            PrestaShopLogger::addLog("CurrencyRate DB setting error for '$name': " . $e->getMessage(), 3);
            return null;
        }
    }

    public function saveModuleSetting(string $name, $value): bool
    {
        try {
            $existing = Db::getInstance()->getValue('
                SELECT id_setting FROM ' . _DB_PREFIX_ . 'currencyrate_settings
                WHERE name = "' . pSQL($name) . '"
            ');

            $data = [
                'value' => pSQL($value),
                'date_upd' => date('Y-m-d H:i:s')
            ];

            if ($existing) {
                $result = Db::getInstance()->update('currencyrate_settings', $data, 'id_setting = ' . (int)$existing);
            } else {
                $data['name'] = pSQL($name);
                $data['date_add'] = date('Y-m-d H:i:s');
                $result = Db::getInstance()->insert('currencyrate_settings', $data);
            }

            PrestaShopLogger::addLog("CurrencyRate DEBUG - Save result for '$name': " . ($result ? 'SUCCESS' : 'FAILED'), 1);

            return $result;
        } catch (\Exception $e) {
            PrestaShopLogger::addLog("CurrencyRate save DB setting error for '$name': " . $e->getMessage(), 3);
            return false;
        }
    }
}