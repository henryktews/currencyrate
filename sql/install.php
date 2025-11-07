<?php

$sql = [];

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'currency_rate` (
    `id_currency_rate` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `currency_code` VARCHAR(3) NOT NULL,
    `currency_name` VARCHAR(100) NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `rate` DECIMAL(10,6) NOT NULL,
    `date` DATE NOT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id_currency_rate`),
    UNIQUE KEY `currency_code_date` (`currency_code`, `date`),
    KEY `date` (`date`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'currencyrate_settings` (
    `id_setting` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `value` TEXT NULL,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_setting`),
    UNIQUE KEY `name` (`name`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

return implode(' ', $sql);