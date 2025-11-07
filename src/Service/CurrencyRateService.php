<?php

namespace CurrencyRate\Service;

use CurrencyRate\Repository\CurrencyRateRepository;
use PrestaShopLogger;

class CurrencyRateService
{
    private NbpApiService $nbpApiService;
    private CurrencyRateRepository $repository;

    public function __construct(NbpApiService $nbpApiService, CurrencyRateRepository $repository)
    {
        $this->nbpApiService = $nbpApiService;
        $this->repository = $repository;
    }

    public function updateCurrencyRates(): bool
    {
        try {
            $rates = $this->nbpApiService->fetchCurrentRates();
            return $this->repository->saveRates($rates);
        } catch (\Exception $e) {
            PrestaShopLogger::addLog("CurrencyRate module error: " . $e->getMessage(), 3);
            return false;
        }
    }

    public function updateHistoricalRates(): bool
    {
        try {
            $rates = $this->nbpApiService->fetchLast30DaysRates();
            $success = true;

            foreach ($rates as $dateRates) {
                if (!$this->repository->saveRates($dateRates)) {
                    $success = false;
                }
            }

            return $success;
        } catch (\Exception $e) {
            PrestaShopLogger::addLog("CurrencyRate historical update error: " . $e->getMessage(), 3);
            return false;
        }
    }

    public function getHistoricalRates(int $page = 1, int $limit = 10, string $sort = 'date', string $order = 'DESC'): array
    {
        return $this->repository->getPaginatedRates($page, $limit, $sort, $order);
    }

    public function getProductPricesInAllCurrencies(int $productId, $price): array
    {
        PrestaShopLogger::addLog("CurrencyRate DEBUG - getProductPricesInAllCurrencies START - Product: $productId, Price: $price", 1);

        $price = $this->normalizePrice($price);

        $currentRates = $this->repository->getActiveLatestRates();

        PrestaShopLogger::addLog("CurrencyRate DEBUG - Active rates count: " . count($currentRates), 1);

        $convertedPrices = [];

        foreach ($currentRates as $rate) {
            $convertedPrice = round($price * $rate['rate'], 2);
            PrestaShopLogger::addLog("CurrencyRate DEBUG - Converting: $price * {$rate['rate']} = $convertedPrice for {$rate['currency_code']}", 1);

            $convertedPrices[] = [
                'currency_code' => $rate['currency_code'],
                'currency_name' => $rate['currency_name'] ?? $rate['currency_code'],
                'converted_price' => $convertedPrice,
                'rate' => $rate['rate'],
                'base_price' => $price
            ];
        }

        PrestaShopLogger::addLog("CurrencyRate DEBUG - Final converted prices count: " . count($convertedPrices), 1);

        return $convertedPrices;
    }

    public function getAllCurrenciesWithStatus(): array
    {
        return $this->repository->getAllCurrenciesWithStatus();
    }

    public function updateCurrencyStatus(string $currencyCode, bool $isActive): bool
    {
        return $this->repository->updateCurrencyStatus($currencyCode, $isActive);
    }

    public function getModuleSetting(string $name, bool $default = false): bool
    {
        try {
            $value = $this->repository->getModuleSetting($name);

            PrestaShopLogger::addLog("CurrencyRate DEBUG - Setting '$name': " . ($value ?: 'NULL') . ", default: " . ($default ?: 'NULL'), 1);

            if ($value === null || $value === '') {
                return $default;
            }

            if ($value === '0' || $value === '1') {
                return (bool)$value;
            }

            return $value;

        } catch (\Exception $e) {
            PrestaShopLogger::addLog("CurrencyRate setting error for '$name': " . $e->getMessage(), 2);
            return $default;
        }
    }

    public function saveModuleSetting(string $name, $value): bool
    {
        try {
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }

            PrestaShopLogger::addLog("CurrencyRate DEBUG - Saving setting '$name': $value", 1);

            return $this->repository->saveModuleSetting($name, $value);
        } catch (\Exception $e) {
            PrestaShopLogger::addLog("CurrencyRate save setting error for '$name': " . $e->getMessage(), 3);
            return false;
        }
    }

    public function isCurrencyActive(string $currencyCode): bool
    {
        $currencies = $this->getAllCurrenciesWithStatus();
        foreach ($currencies as $currency) {
            if ($currency['currency_code'] === $currencyCode) {
                return (bool)$currency['is_active'];
            }
        }
        return true;
    }

    public function getTotalHistoricalRecords(): int
    {
        return $this->repository->getTotalCount();
    }

    private function normalizePrice($price): float
    {
        if (is_float($price)) {
            return $price;
        }

        if (is_int($price)) {
            return (float) $price;
        }

        if (is_string($price)) {
            $price = trim($price);
            $price = str_replace(',', '.', $price);
            $price = preg_replace('/[^\d\.\-]/', '', $price);

            if ($price === '' || $price === '.') {
                return 0.0;
            }

            return (float) $price;
        }

        return 0.0;
    }
}