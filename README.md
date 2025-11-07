# Currency Rate Module for PrestaShop

Moduł do wyświetlania aktualnych kursów walut na stronie produktu w PrestaShop.

## Funkcjonalności

- Wyświetlanie cen produktu w różnych walutach
- Automatyczne pobieranie kursów z NBP
- Możliwość wyłączenia poszczególnych walut

## Wymagania

- PrestaShop 8.x lub 9.x
- PHP 7.2+
- Włączone rozszerzenie cURL

## Instalacja

1. Skopiuj folder `currencyrate` do katalogu `modules/` Twojego sklepu
2. Przejdź do Panel Administracyjny → Moduły → Module Manager
3. Znajdź moduł "Currency Rate" i kliknij "Install"
4. Po instalacji przejdź do Improve → Currency Rates

## Konfiguracja

### 1. Pierwsze uruchomienie

Po instalacji:
1. Przejdź do **Improve → Currency Rates**
2. Kliknij **"Update Rates Now"** aby pobrać aktualne kursy walut
3. Kursy zostaną pobrane z API NBP i zapisane w bazie danych

### 2. Konfiguracja wyświetlania

1. Przejdź do **Improve → Currency Rates**
2. Kliknij **"Currency Settings"**
3. Skonfiguruj ustawienia:
    - **Show on Product Page** - włącz/wyłącz wyświetlanie na stronie produktu
    - **Active Currencies** - wybierz które waluty mają być pokazywane

### 3. Wyłączanie walut

Aby wyłączyć wyświetlanie konkretnych walut:
1. Przejdź do **Improve → Currency Rates → Currency Settings**
2. Odznacz waluty których nie chcesz pokazywać
3. Zapisz ustawienia

## Automatyczna aktualizacja kursów (CRON)

Aby kursy walut aktualizowały się automatycznie, dodaj zadanie cron:

### Opcja 1: Użyj pliku cron.php

```bash
0 13 * * * /usr/bin/php /path/to/prestashop/modules/currencyrate/cron.php