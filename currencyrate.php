<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class CurrencyRate extends Module
{
    public function __construct()
    {
        $this->name = 'currencyrate';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Developer';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => '9.9.9',
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Currency Rate');
        $this->description = $this->l('Display current and historical currency rates');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    public function install(): bool
    {
        return parent::install() &&
            $this->installDatabase() &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayProductAdditionalInfo') &&
            $this->installTab();
    }

    public function upgrade($version): bool
    {
        if (version_compare($version, '1.0.1', '<')) {
            include_once dirname(__FILE__) . '/sql/upgrade-1.0.1.php';
        }
        return true;
    }

    public function uninstall(): bool
    {
        return parent::uninstall() &&
            $this->uninstallDatabase() &&
            $this->uninstallTab();
    }

    private function installDatabase(): bool
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'currency_rate` (
            `id_currency_rate` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `currency_code` VARCHAR(3) NOT NULL,
            `rate` DECIMAL(10,6) NOT NULL,
            `date` DATE NOT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id_currency_rate`),
            UNIQUE KEY `currency_code_date` (`currency_code`, `date`),
            KEY `date` (`date`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        return Db::getInstance()->execute($sql);
    }

    private function uninstallDatabase(): bool
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'currency_rate`');
    }

    private function installTab(): bool
    {
        $tab = new Tab();
        $tab->class_name = 'AdminCurrencyRate';
        $tab->module = $this->name;
        $tab->id_parent = (int)Tab::getIdFromClassName('IMPROVE');
        $tab->icon = 'account_balance';
        $langs = Language::getLanguages();
        foreach ($langs as $l) {
            $tab->name[$l['id_lang']] = $this->l('Currency Rates');
        }
        return $tab->add();
    }

    private function uninstallTab(): bool
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminCurrencyRate');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        return true;
    }

    public function hookDisplayHeader(): void
    {
        $this->context->controller->registerStylesheet(
            'currencyrate-css',
            'modules/' . $this->name . '/views/css/front.css',
            ['media' => 'all', 'priority' => 150]
        );
    }

    public function hookDisplayProductAdditionalInfo($params): bool|string
    {
        PrestaShopLogger::addLog("CurrencyRate DEBUG - Hook displayProductAdditionalInfo START", 1);

        try {
            $currencyService = $this->getCurrencyRateService();
            $showOnProductPage = (bool)$currencyService->getModuleSetting('show_on_product_page', 1);

            PrestaShopLogger::addLog("CurrencyRate DEBUG - showOnProductPage: " . ($showOnProductPage ? 'YES' : 'NO'), 1);

            if (!$showOnProductPage) {
                PrestaShopLogger::addLog("CurrencyRate DEBUG - Display disabled by settings", 1);
                return '';
            }
        } catch (Exception $e) {
            PrestaShopLogger::addLog('CurrencyRate settings error: ' . $e->getMessage(), 2);
            return '';
        }

        $product = $params['product'];
        if (!Validate::isLoadedObject($product)) {
            PrestaShopLogger::addLog("CurrencyRate DEBUG - Product not loaded or invalid", 1);
            return '';
        }

        PrestaShopLogger::addLog("CurrencyRate DEBUG - Product ID: " . $product->id . ", Price: " . $product->price, 1);

        try {
            $currencyService = $this->getCurrencyRateService();

            $productPrice = $product->price;

            if (is_string($productPrice)) {
                $productPrice = (float) str_replace(',', '.', $productPrice);
            } elseif (is_numeric($productPrice)) {
                $productPrice = (float) $productPrice;
            } else {
                $productPrice = 0.0;
            }

            PrestaShopLogger::addLog("CurrencyRate DEBUG - Converted price: " . $productPrice, 1);

            $convertedPrices = $currencyService->getProductPricesInAllCurrencies($product->id, $productPrice);

            PrestaShopLogger::addLog("CurrencyRate DEBUG - Converted prices count: " . count($convertedPrices), 1);

            if (empty($convertedPrices)) {
                PrestaShopLogger::addLog("CurrencyRate DEBUG - No converted prices available", 1);
                return '';
            }

            $this->context->smarty->assign([
                'converted_prices' => $convertedPrices,
                'product_price' => $productPrice,
            ]);

            PrestaShopLogger::addLog("CurrencyRate DEBUG - Template variables assigned, returning template", 1);

            return $this->display(__FILE__, 'views/templates/front/product_currencies.tpl');
        } catch (Exception $e) {
            PrestaShopLogger::addLog('CurrencyRate module error: ' . $e->getMessage(), 3);
            return '';
        }
    }

    public function getContent(): void
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminCurrencyRate'));
    }


    private function getCurrencyRateService(): \CurrencyRate\Service\CurrencyRateService
    {
        try {
            PrestaShopLogger::addLog("CurrencyRate: Initializing CurrencyRateService", 1);

            $nbpApiService = new CurrencyRate\Service\NbpApiService();
            $repository = new CurrencyRate\Repository\CurrencyRateRepository();

            $service = new CurrencyRate\Service\CurrencyRateService($nbpApiService, $repository);

            PrestaShopLogger::addLog("CurrencyRate: Service initialized successfully", 1);

            return $service;

        } catch (Exception $e) {
            PrestaShopLogger::addLog("CurrencyRate: Error initializing service: " . $e->getMessage(), 3);
            throw $e;
        }
    }
}