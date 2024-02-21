<?php

namespace xsme\Metroport;

class SMSApi
{
    /**
     * Adres do API.
     * 
     * @var string
     */
    private $url = null;

    /**
     * Login uzywtkonika z dostepem do API.
     * 
     * @var string
     */
    private $login = null;

    /**
     * Haslo uzytkownika z dostepem do API.
     * 
     * @var string
     */
    private $password = null;

    /**
     * Ciasteczko tworzone po udanym zalogowaniu danymi uzytkownika.
     * 
     * @var string|null
     */
    private $cookie = null; 

    /**
     * 
     * 
     * @var int|null
     */
    private $code = null;

    /**
     * 
     * 
     * @var mixed|null
     */
    private $response = null;

    /**
     * Konstruktor dla klasy.
     * Wykonanie pierwszego zapytanie o autoryzację.
     * 
     * @return void
     */
    public function __construct(string $url, string $login, string $password) {
        $this->url      = $url;
        $this->login    = $login;
        $this->password = $password;

        $this->makeRequest("admins/auth/login", 'POST', [
            'login'    => $this->login,
            'password' => $this->password,
        ]);

        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $this->response, $matches);
        
        $this->cookie = implode("; ", $matches[1]);
    }

    /**
     * Przygotowanie i wyslanie zapiytania do API.
     * 
     * @param string $method
     * @param mixed $data
     * @return array
     *  [code]     => int
     *  [response] => mixed
     */
    private function makeRequest(string $method, string $callType, $data = null)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->url.$method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (!$this->cookie) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HEADER, true);
        }

        if ($this->cookie) {
            curl_setopt($ch, CURLOPT_COOKIE, $this->cookie);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $callType);
            curl_setopt($ch, CURLOPT_HEADER, false);
        }
        
        curl_setopt($ch, CURLOPT_POST, (in_array($callType, ["POST", "PUT", "DELETE"]))
            ? true
            : false
        );

        curl_setopt($ch, CURLOPT_POSTFIELDS, (in_array($callType, ["POST", "PUT", "DELETE"]))
            ? json_encode($data)
            : null
        );

        curl_setopt($ch, CURLOPT_HTTPHEADER, (in_array($callType, ["POST", "PUT", "DELETE"]))
            ? array('Content-Type: application/json; charset=utf-8', 'Content-Length: '.strlen(json_encode($data)))
            : array('Content-Type: application/json; charset=utf-8')
        );

        if ($method !== "admins/auth/login") {
            // die;
        }

        $this->response = curl_exec($ch);

        $this->code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return [
            'code' => $this->code, 
            'response' => json_decode($this->response)
        ];
    }
    
    /**
     * Dodanie parametrow do url, najczesciej są to filtry dla zapytania
     * 
     * @param string $url        podstawowy adres zapytania
     * @param array  $parameters tablica z parametrami
     * @return string            zwraca gotowy link zapytania
     */
    private static function makeEndpoint(string $url, array $parameters)
    {
        $query = http_build_query($parameters, '', '&&');
        $url .= ($query) ? "/?" . $query : null;
        return $url;
    }

    /**
     * Wysłanie wiadomości SMS
     * 
     * @param string $number numer skladajacy sie z 11 cyfr z kodem kraju np. 48500600700
     * @param string $text   tresc wiasomosci ktora chcemy wyslac
     * @param string $sender numer skladajacy sie z 11 cyfr z kodem z kraju np. 48500600700 lub pusta zmienna dla wysylki losowej
     * @param int    $itme   czas w ktorym ma byc wyslany sms jako timestamp, pusta wartosc wysylka natychmiastowa
     * @return array
     *  [code]     => int
     *  [response] => array
     */
    public function postSms(string $number, string $text, string $sender, int $time = null)
    {
        $parameters = [
            'message' => $text, 
            'msisdn'  => $number, 
            'sender'  => $sender, 
        ];

        if (!$time) {
            $parameters['dateToSend'] = $time;
        }

        return $this->makeRequest("sms/SMS", "POST", $parameters);
    }

    /**
     * Sprawdzanie statusu wiadomości SMS
     * 
     * @param int $id identyfikator wiadomosci, jest zwracany po wyslaniu wiadomosci sms
     * @return array
     *  [code]     => int
     *  [response] => array
     */
    public function getSms(int $id)
    {
        return $this->makeRequest("sms/SMS/{$id}", "GET");
    }

    /**
     * Pobieranie listy dostępnych numerów
     * 
     * @param bool $random dla 'true' tylko losowe, 'false' tylko statyczne
     * @return array
     *  [code]     => int
     *  [response] => array
     */
    public function getNumbers(bool $random = true)
    {
        $parameters = [
            'random' => $random ? 'true' : 'false',
        ];
        $endpoint = static::makeEndpoint("sms/Pool", $parameters);

        return $this->makeRequest($endpoint, "GET");
    }

    /**
     * Pobieranie listy wykupionych usług
     * 
     * @return array
     *  [code]     => int
     *  [response] => array
     */
    public function getServices()
    {
        return $this->makeRequest("sms/Tariff/accounttariffs", "GET");
    }
}