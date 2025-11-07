<?php

namespace CurrencyRate\Service;

use PrestaShopLogger;

class NbpApiService
{
    public function __construct()
    {
    }

    public function fetchCurrentRates(): array
    {
        try {
            $url = 'https://api.nbp.pl/api/exchangerates/tables/A/?format=json';
            PrestaShopLogger::addLog("CurrencyRate: Fetching current rates from: " . $url, 1);

            $response = $this->makeHttpRequest($url);

            if (empty($response)) {
                throw new \Exception('Empty response from API');
            }

            $data = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON decode error: ' . json_last_error_msg());
            }

            if (!isset($data[0]['rates'])) {
                throw new \Exception('Invalid API response structure');
            }

            PrestaShopLogger::addLog("CurrencyRate: Successfully fetched " . count($data[0]['rates']) . " rates", 1);

            return $this->formatRatesData($data[0]);

        } catch (\Exception $e) {
            PrestaShopLogger::addLog("CurrencyRate API Error - Current Rates: " . $e->getMessage(), 3);
            throw new \Exception('Failed to fetch current rates: ' . $e->getMessage());
        }
    }

    public function fetchLast30DaysRates(): array
    {
        try {
            $endDate = date('Y-m-d');
            $startDate = date('Y-m-d', strtotime('-30 days'));
            $url = "https://api.nbp.pl/api/exchangerates/tables/A/{$startDate}/{$endDate}/?format=json";

            PrestaShopLogger::addLog("CurrencyRate: Fetching historical rates from: " . $url, 1);

            $response = $this->makeHttpRequest($url);

            if (empty($response)) {
                throw new \Exception('Empty response from API');
            }

            $data = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON decode error: ' . json_last_error_msg());
            }

            $rates = [];
            foreach ($data as $dayData) {
                if (isset($dayData['rates'])) {
                    $rates[$dayData['effectiveDate']] = $this->formatRatesData($dayData);
                }
            }

            PrestaShopLogger::addLog("CurrencyRate: Successfully fetched historical data for " . count($rates) . " days", 1);

            return $rates;

        } catch (\Exception $e) {
            PrestaShopLogger::addLog("CurrencyRate API Error - Historical Rates: " . $e->getMessage(), 3);
            throw new \Exception('Failed to fetch historical rates: ' . $e->getMessage());
        }
    }

    private function makeHttpRequest(string $url): string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'PrestaShop CurrencyRate Module/1.0');

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            PrestaShopLogger::addLog("CurrencyRate: cURL HTTP Code: " . $httpCode, 1);

            if ($httpCode === 200 && $response) {
                return $response;
            } else {
                throw new \Exception('cURL error: ' . $error . ' HTTP Code: ' . $httpCode);
            }
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 15,
                'user_agent' => 'PrestaShop CurrencyRate Module/1.0',
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            $error = error_get_last();
            throw new \Exception('file_get_contents error: ' . ($error['message'] ?? 'Unknown error'));
        }

        return $response;
    }

    private function formatRatesData(array $apiData): array
    {
        $rates = [];
        $effectiveDate = $apiData['effectiveDate'] ?? date('Y-m-d');

        foreach ($apiData['rates'] as $rate) {
            $rates[] = [
                'currency_code' => $rate['code'],
                'rate' => (float) $rate['mid'],
                'date' => $effectiveDate,
                'currency_name' => $rate['currency']
            ];
        }

        return $rates;
    }
}