<?php

namespace App\Controller;

use App\Entity\ExchangeRate;
use App\Repository\CurrencyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ExchangeController extends AbstractController
{
    /**
     * @var array
     */
    private $currencyExchangeRateArray;
    /**
     * @var false|int
     */
    private $time;


    /**
     * Set the time for the online rates data
     *
     * @param $time
     */
    public function setTime($time): void
    {
        $this->time = $time;
    }


    /**
     * Get the time for the online rates data
     *
     * @return false|int
     */
    public function getTime()
    {
        return $this->time;
    }


    /**
     * @Route("/exchange", name="exchange")
     * @param CurrencyRepository $repo
     * @return Response
     */
    public function index(CurrencyRepository $repo): Response
    {
        $currencies = $repo->findAll();
        $codes = [];
        foreach($currencies as $currency) {
            $codes[] = $currency->getCode();
        }
        return $this->render('exchange/index.html.twig', [
            'currencycodes' => $codes,
        ]);
    }


    /**
     * Function for the form action, do some basic sanitizing
     * TODO: Validation through "official channels"
     *
     * @param Request $request
     * @return Response
     * @Route ("/exchange/result", name="exchange-it")
     */
    public function exchange(Request $request): Response
    {
        $formSubmit = $request->request->all();
        $currencyOrigin = filter_var(trim($formSubmit['currencyin']), FILTER_SANITIZE_STRING);
        $currencyDest = filter_var(trim($formSubmit['currencyout']), FILTER_SANITIZE_STRING);
        $currencyOriginRate = $this->getExchangeRate($currencyOrigin);
        $currencyDestRate = $this->getExchangeRate($currencyDest);
        $amount = filter_var(trim($formSubmit['amount']), FILTER_SANITIZE_STRING);

        $converted = round(floatval($amount / $currencyOriginRate) / $currencyDestRate, 2);

        return $this->render('exchange/result.html.twig', [
            'origin' => $currencyOrigin,
            'dest' => $currencyDest,
            'amount' => $amount,
            'result' => $converted,
        ]);
    }


    /**
     * Helper function to retrieve the data from the exchange rate site
     *
     * @return array
     */
    private function getRates(): array
    {
        $exchangeRateUrl = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';
        $ratesUpdateTime = '14:15';

        $ch = curl_init();
        $arr_options = [
            CURLOPT_URL => $exchangeRateUrl,
            CURLOPT_RETURNTRANSFER => TRUE,
        ];
        curl_setopt_array($ch, $arr_options);
        $str_xml = curl_exec($ch);

        if (curl_error($ch)) {
            // write error to log
            // $logger = new ConsoleLogger; // TODO: find out how this works
            // $logger->ERROR('Curl failed to retrieve the data from ' . $exchangeRateUrl);
            print 'Curl failed to retrieve the data from ' . $exchangeRateUrl;
        }
        $xml = simplexml_load_string($str_xml);
        $json = json_encode($xml);
        $array = json_decode($json, TRUE);

        // Set a time-stamp for the latest version
        $this->setTime(strtotime($array['Cube']['Cube']['@attributes']['time'] . ' ' . $ratesUpdateTime));

        $this->flattenExchangeRateArray($array['Cube']['Cube']['Cube']);
        return $this->currencyExchangeRateArray;
    }

    
    /**
     * Helper function that flattens the array of currency exchange rates into a flat currency code indexed array
     *
     * @param array $arr_exchange
     */
    private function flattenExchangeRateArray(array $arr_exchange): void
    {
        $currencyExchangeRateArray = [];
        foreach ($arr_exchange as $arr_cube) {
            $currencyExchangeRateArray[$arr_cube['@attributes']['currency']] = $arr_cube['@attributes']['rate'];
        }
        $this->currencyExchangeRateArray = $currencyExchangeRateArray;
    }


    /**
     * Function to retrieve the exchange rate for a given currency.
     * Check the time stamp. If the time is after 14:15 it is
     *
     * @param $currencyCode
     */
    private function getCurrentExchangeRate($currencyCode)
    {
        $exchangeRates = $this->getRates();
        $timestamp = $this->getTime();

        // Check if the data on the website should have been updated
        if (intval(date('G')) > 14 && intval(date('i')) > 15) {
            $upToDate = intval(date('j', $timestamp)) > intval(date('j'));
        }
        // We should have
        else {

        }
        $sql = "SELECT DATE_FORMAT(whatever.createdAt, '%Y-%m-%d') FORM whatever...";
        $em = $this->getDoctrine()->getManager();
        $em->getConnection()->exec($sql);
    }


    /**
     * @param string $currency
     * @return mixed
     */
    private function getExchangeRate(string $currency)
    {
        $this->getRates();
        return floatval($this->currencyExchangeRateArray[$currency]);
    }


    /**
     * Get the exchange rates from the website and put them in the database with the timestamp of the data
     * This function should be performed one a day after 14:15, the update time of the exchange rate data
     */
    public function putExchangeRates(): void
    {
        $this->getRates();
        $entityManager = $this->getDoctrine()->getManager();
        $ratesArray = $this->currencyExchangeRateArray;
        foreach($ratesArray as $code => $rate) {
            $exchangeRate = new ExchangeRate();
            $exchangeRate->setCode($code);
            $exchangeRate->setRate($rate);
            $exchangeRate->setTime($this->time);
            $entityManager->persist($exchangeRate);
        }
        $entityManager->flush();
    }
}
