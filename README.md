# API Metroport SMS
## SMS API służy do wysyłania, sprawdzania statusu smsów, bilansu konta

Dostep do API jest przyznawany przez Metroport oraz wszystkie potrzebne dane.

## Instalacja

Bibliotekę pobieramy i instalujemy poprzez composer:

```sh
composer require xsme/php-api-metroport-sms
```

## Funkcje

Spis wszystkich funkcji z opisem i odpowiedzią zwrotną.

```php
// $location - uzyskujemy z Metroport
// $username - uzyskujemy z Metroport
// $password - uzyskujemy z Metroport
$sms = new SMSApi($location, $username, $password)

// Wysylanie wiadomosci SMS
$test = $sms->postSms('48500600700', "testowa wiadomosc", '48500600700');

// Lista dostępnych numerów, true - tylko losowe / false - tylko statyczne
$test = $sms->getNumbers(false);

// Sprawdzanie statusu SMS, jako paramert podajemy ID wczesniej wyslanego SMSa
$test = $sms->getSms(663214);

// Pobranie listy wykupionych usług
$test = $sms->getServices();
```