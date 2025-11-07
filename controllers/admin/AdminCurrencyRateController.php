<?php

use CurrencyRate\Service\CurrencyRateService;

class AdminCurrencyRateController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'currency_rate';
        $this->identifier = 'id_currency_rate';
        $this->list_id = 'currency_rate';

        parent::__construct();

        $this->fields_list = [
            'id_currency_rate' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'currency_code' => [
                'title' => $this->l('Currency Code'),
                'align' => 'center',
            ],
            'currency_name' => [
                'title' => $this->l('Currency Name'),
                'align' => 'center',
            ],
            'rate' => [
                'title' => $this->l('Rate'),
                'align' => 'center',
                'type' => 'float',
            ],
            'date' => [
                'title' => $this->l('Date'),
                'align' => 'center',
                'type' => 'date',
            ],
            'is_active' => [
                'title' => $this->l('Active'),
                'align' => 'center',
                'type' => 'bool',
                'active' => 'status',
                'orderby' => false,
            ],
            'updated_at' => [
                'title' => $this->l('Last Updated'),
                'align' => 'center',
                'type' => 'datetime',
            ],
        ];

        $this->bulk_actions = [
            'enableSelection' => [
                'text' => $this->l('Enable selection'),
                'icon' => 'icon-power-off text-success',
            ],
            'disableSelection' => [
                'text' => $this->l('Disable selection'),
                'icon' => 'icon-power-off text-danger',
            ],
            'delete' => [
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
            ],
        ];

        $this->_select = 'a.*';
        $this->_orderBy = 'date';
        $this->_orderWay = 'DESC';
    }

    public function initPageHeaderToolbar(): void
    {
        if (empty($this->display) || $this->display == 'list') {
            $this->page_header_toolbar_btn['update_rates'] = [
                'href' => self::$currentIndex . '&update_rates=1&token=' . $this->token,
                'desc' => $this->l('Update Rates Now'),
                'icon' => 'process-icon-refresh'
            ];

            $this->page_header_toolbar_btn['update_historical'] = [
                'href' => self::$currentIndex . '&update_historical=1&token=' . $this->token,
                'desc' => $this->l('Update Historical Data'),
                'icon' => 'process-icon-download'
            ];

            $this->page_header_toolbar_btn['currency_settings'] = [
                'href' => self::$currentIndex . '&config_currencies=1&token=' . $this->token,
                'desc' => $this->l('Currency Settings'),
                'icon' => 'process-icon-cogs'
            ];
        }

        parent::initPageHeaderToolbar();
    }

    public function initProcess(): void
    {
        parent::initProcess();

        if (Tools::getValue('update_rates')) {
            $this->action = 'update_rates';
        }

        if (Tools::getValue('update_historical')) {
            $this->action = 'update_historical';
        }

        if (Tools::getValue('config_currencies')) {
            $this->display = 'config_currencies';
        }
    }

    public function postProcess(): void
    {
        if (Tools::getValue('update_rates')) {
            $this->processUpdateRates();
        }

        if (Tools::getValue('update_historical')) {
            $this->processUpdateHistorical();
        }

        if (Tools::isSubmit('submitCurrencySettings')) {
            $this->processCurrencySettings();
        }

        parent::postProcess();
    }

    public function renderList(): string
    {
        if ($this->display == 'config_currencies') {
            return $this->renderConfigForm();
        }

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->_defaultOrderBy = 'date';
        $this->_defaultOrderWay = 'DESC';

        $list = parent::renderList();

        $buttons = $this->renderTopButtons();

        return $buttons . $list;
    }

    private function renderTopButtons(): string
    {
        $html = '<div class="panel">';
        $html .= '<div class="panel-heading">' . $this->l('Quick Actions') . '</div>';
        $html .= '<div class="panel-body">';
        $html .= '<a href="' . self::$currentIndex . '&update_rates=1&token=' . $this->token . '" class="btn btn-primary">';
        $html .= '<i class="icon-refresh"></i> ' . $this->l('Update Current Rates');
        $html .= '</a> ';
        $html .= '<a href="' . self::$currentIndex . '&update_historical=1&token=' . $this->token . '" class="btn btn-default">';
        $html .= '<i class="icon-download"></i> ' . $this->l('Update Historical Data');
        $html .= '</a> ';
        $html .= '<a href="' . self::$currentIndex . '&config_currencies=1&token=' . $this->token . '" class="btn btn-success">';
        $html .= '<i class="icon-cogs"></i> ' . $this->l('Currency Settings');
        $html .= '</a>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    private function renderConfigForm(): string
    {
        $currencyService = $this->getCurrencyRateService();
        $currencies = $currencyService->getAllCurrenciesWithStatus();

        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Currency Display Settings'),
                    'icon' => 'icon-cogs'
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Show on Product Page'),
                        'name' => 'show_on_product_page',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'show_on_product_page_on',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            ],
                            [
                                'id' => 'show_on_product_page_off',
                                'value' => 0,
                                'label' => $this->l('No')
                            ]
                        ],
                        'desc' => $this->l('Display currency table on product pages'),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Auto Update Rates'),
                        'name' => 'auto_update_rates',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'auto_update_rates_on',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            ],
                            [
                                'id' => 'auto_update_rates_off',
                                'value' => 0,
                                'label' => $this->l('No')
                            ]
                        ],
                        'desc' => $this->l('Automatically update rates daily via cron'),
                    ],
                    [
                        'type' => 'html',
                        'name' => 'currency_selector',
                        'html_content' => $this->generateCurrencySelector($currencies),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submitCurrencySettings'
                ]
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this->module;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitCurrencySettings';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminCurrencyRate', false);
        $helper->token = $this->token;

        $helper->tpl_vars = [
            'fields_value' => [
                'show_on_product_page' => $currencyService->getModuleSetting('show_on_product_page', 1),
                'show_historical_page' => $currencyService->getModuleSetting('show_historical_page', 1),
                'auto_update_rates' => $currencyService->getModuleSetting('auto_update_rates', 1),
            ],
            'languages' => $this->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        foreach ($currencies as $currency) {
            $helper->tpl_vars['fields_value']['currency_' . $currency['currency_code']] = $currency['is_active'];
        }

        $form = $helper->generateForm([$fields_form]);

        $back_button = '<div class="panel-footer">';
        $back_button .= '<a href="' . $this->context->link->getAdminLink('AdminCurrencyRate') . '" class="btn btn-default">';
        $back_button .= '<i class="icon-arrow-left"></i> ' . $this->l('Back to list');
        $back_button .= '</a>';
        $back_button .= '</div>';

        return $form . $back_button;
    }

    private function generateCurrencySelector($currencies): string
    {
        $html = '<div class="panel">';
        $html .= '<div class="panel-heading">' . $this->l('Active Currencies') . '</div>';
        $html .= '<div class="panel-body">';
        $html .= '<p>' . $this->l('Select which currencies to display on the front office:') . '</p>';

        $html .= '<div class="row">';
        foreach ($currencies as $currency) {
            $html .= '<div class="col-md-4">';
            $html .= '<div class="checkbox">';
            $html .= '<label>';
            $html .= '<input type="checkbox" name="currency_' . $currency['currency_code'] . '" value="1" ' .
                ($currency['is_active'] ? 'checked="checked"' : '') . '>';
            $html .= '<strong>' . $currency['currency_code'] . '</strong> - ' . $currency['currency_name'];
            $html .= ' <small class="text-muted">(' . $currency['record_count'] . ' records)</small>';
            $html .= '</label>';
            $html .= '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function processUpdateRates(): void
    {
        try {
            $currencyService = $this->getCurrencyRateService();

            if ($currencyService->updateCurrencyRates()) {
                $this->confirmations[] = $this->l('Currency rates updated successfully');
            } else {
                $this->errors[] = $this->l('Failed to update currency rates - check logs for details');
            }
        } catch (Exception $e) {
            $this->errors[] = $this->l('Error updating rates: ') . $e->getMessage();
            PrestaShopLogger::addLog('CurrencyRate update error: ' . $e->getMessage(), 3);
        }
    }

    public function processUpdateHistorical(): void
    {
        try {
            $currencyService = $this->getCurrencyRateService();

            if ($currencyService->updateHistoricalRates()) {
                $this->confirmations[] = $this->l('Historical rates updated successfully');
            } else {
                $this->errors[] = $this->l('Failed to update historical rates - check logs for details');
            }
        } catch (Exception $e) {
            $this->errors[] = $this->l('Error updating historical rates: ') . $e->getMessage();
            PrestaShopLogger::addLog('CurrencyRate historical update error: ' . $e->getMessage(), 3);
        }
    }

    private function processCurrencySettings(): void
    {
        $currencyService = $this->getCurrencyRateService();

        $currencies = $currencyService->getAllCurrenciesWithStatus();
        foreach ($currencies as $currency) {
            $isActive = (bool)Tools::getValue('currency_' . $currency['currency_code'], 0);
            $currencyService->updateCurrencyStatus($currency['currency_code'], $isActive);
        }

        $currencyService->saveModuleSetting('show_on_product_page', (int)Tools::getValue('show_on_product_page', 1));
        $currencyService->saveModuleSetting('show_historical_page', (int)Tools::getValue('show_historical_page', 1));
        $currencyService->saveModuleSetting('auto_update_rates', (int)Tools::getValue('auto_update_rates', 1));

        $this->confirmations[] = $this->l('Currency settings updated successfully');
    }

    public function processBulkEnableSelection(): void
    {
        if (is_array($this->boxes)) {
            $currencyService = $this->getCurrencyRateService();
            $count = 0;
            foreach ($this->boxes as $id) {
                $currencyCode = Db::getInstance()->getValue('
                    SELECT currency_code FROM ' . _DB_PREFIX_ . 'currency_rate 
                    WHERE id_currency_rate = ' . (int)$id
                );
                if ($currencyCode && $currencyService->updateCurrencyStatus($currencyCode, true)) {
                    $count++;
                }
            }
            $this->confirmations[] = sprintf($this->l('%d currencies have been enabled'), $count);
        }
    }

    public function processBulkDisableSelection(): void
    {
        if (is_array($this->boxes)) {
            $currencyService = $this->getCurrencyRateService();
            $count = 0;
            foreach ($this->boxes as $id) {
                $currencyCode = Db::getInstance()->getValue('
                    SELECT currency_code FROM ' . _DB_PREFIX_ . 'currency_rate 
                    WHERE id_currency_rate = ' . (int)$id
                );
                if ($currencyCode && $currencyService->updateCurrencyStatus($currencyCode, false)) {
                    $count++;
                }
            }
            $this->confirmations[] = sprintf($this->l('%d currencies have been disabled'), $count);
        }
    }

    private function getCurrencyRateService(): CurrencyRateService
    {
        try {
            if (!class_exists('CurrencyRate\Service\NbpApiService')) {
                require_once _PS_MODULE_DIR_ . 'currencyrate/src/Service/NbpApiService.php';
                require_once _PS_MODULE_DIR_ . 'currencyrate/src/Repository/CurrencyRateRepository.php';
                require_once _PS_MODULE_DIR_ . 'currencyrate/src/Service/CurrencyRateService.php';
            }

            $nbpApiService = new CurrencyRate\Service\NbpApiService();
            $repository = new CurrencyRate\Repository\CurrencyRateRepository();
            return new CurrencyRate\Service\CurrencyRateService($nbpApiService, $repository);

        } catch (Exception $e) {
            PrestaShopLogger::addLog('CurrencyRate service error: ' . $e->getMessage(), 3);

            return new class() {
                public function updateCurrencyRates() { return false; }
                public function updateHistoricalRates() { return false; }
                public function getAllCurrenciesWithStatus() {
                    return Db::getInstance()->executeS('
                        SELECT DISTINCT currency_code, currency_name, 1 as is_active, COUNT(*) as record_count
                        FROM ' . _DB_PREFIX_ . 'currency_rate 
                        GROUP BY currency_code, currency_name
                    ') ?: [];
                }
                public function updateCurrencyStatus($code, $status) {
                    return Db::getInstance()->update(
                        'currency_rate',
                        ['is_active' => (int)$status],
                        'currency_code = "' . pSQL($code) . '"'
                    );
                }
                public function getModuleSetting($name, $default = null) {
                    $result = Db::getInstance()->getValue('
                        SELECT value FROM ' . _DB_PREFIX_ . 'currencyrate_settings
                        WHERE name = "' . pSQL($name) . '"
                    ');
                    return $result ?: $default;
                }
                public function saveModuleSetting($name, $value) {
                    $existing = Db::getInstance()->getValue('
                        SELECT id_setting FROM ' . _DB_PREFIX_ . 'currencyrate_settings
                        WHERE name = "' . pSQL($name) . '"
                    ');

                    if ($existing) {
                        return Db::getInstance()->update('currencyrate_settings', [
                            'value' => pSQL($value),
                            'date_upd' => date('Y-m-d H:i:s')
                        ], 'id_setting = ' . (int)$existing);
                    } else {
                        return Db::getInstance()->insert('currencyrate_settings', [
                            'name' => pSQL($name),
                            'value' => pSQL($value),
                            'date_add' => date('Y-m-d H:i:s'),
                            'date_upd' => date('Y-m-d H:i:s')
                        ]);
                    }
                }
            };
        }
    }
}