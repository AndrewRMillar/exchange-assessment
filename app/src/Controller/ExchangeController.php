<?php

namespace App\Controller;

use App\Entity\ExchangeRate;
use App\Repository\CurrencyRepository;
use Psr\Log\LoggerInterface;
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
     * @var int
     */
    private $xmlTime;
    /**
     * @var int
     */
    private $DBTime;
    /**
     * @var LoggerInterface
     */
    private $logger;


    /**
     * Set the time for the online rates data from the xml
     *
     * @param $xmlTime
     */
    public function setXmlTime($xmlTime): void
    {
        $this->xmlTime = $xmlTime;
    }


    /**
     * Get the time for the online rates data
     *
     * @return int
     */
    public function getXmlTime(): int
    {
        return $this->xmlTime;
    }

    /**
     * @param $time
     */
    private function setDBTime($time)
    {
        $this->DBTime = $time;
    }

    /**
     * @return int
     */
    private function getDBTime(): int
    {
        return $this->DBTime;
    }



    /**
     * @param CurrencyRepository $repo
     * @return Response
     * @Route("/exchange", name="exchange")
     */
    public function index(CurrencyRepository $repo, LoggerInterface $logger): Response
    {
        $this->logger = $logger;
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
            'datetime' => $this->getDBTime(),
        ]);
    }


    /**
     * Helper function to retrieve the data from the exchange rate site
     *
     * @return array
     */
    private function getRates(): array
    {
        // TODO find out where to best make these constants
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
            $this->logger->critical('Curl error: %error', ['%error' => curl_error($ch)]);
            print 'Curl failed to retrieve the data from ' . $exchangeRateUrl;
            print 'Error message: ' . curl_error($ch);
        }
        $xml = simplexml_load_string($str_xml);
        $json = json_encode($xml);
        $array = json_decode($json, TRUE);

        // Set a time-stamp for the latest version
        $this->setXmlTime(strtotime($array['Cube']['Cube']['@attributes']['time'] . ' ' . $ratesUpdateTime));

        $this->flattenExchangeRateArray($array['Cube']['Cube']['Cube']);

        return $this->currencyExchangeRateArray;
    }


    /**
     * Get the exchange rates from the db,
     * @param $code
     * @return object
     */
    private function getDBRate($code): object
    {
        $exchangeArray = $this->getDoctrine()
                ->getRepository(ExchangeRate::class)
                ->findBy(['code' => $code]);
        $DBTime = 0;
        $return = null;
        foreach ($exchangeArray as $exchangeRate) {
            if ($exchangeRate->getTime() > $DBTime) {
                $return = $exchangeRate;
                $DBTime = $exchangeRate->getTime();
            }
        }
        $this->setDBTime($return->getTime());
        return $return;
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
     * Function to return if the time of day is before or after the update time
     * @return bool
     */
    private function beforeUpdate(): bool
    {
        return intval(date('G')) > 14 && intval(date('i')) > 15;
    }


    /**
     * Get the most up te date exchangerate for a single currency
     * @param string $currency
     * @return mixed
     */
    private function getExchangeRate(string $currency)
    {
        $this->getRates();
        $DBRate = $this->getDBRate($currency);
        $dbTime = $this->getDBTime();
        $removeDay = $this->beforeUpdate()? 1: 0;
        // If the db timestamp is bigger that the xml timestamp use it
        if ($dbTime >= $this->getXmlTime()) {
            return $DBRate->getRate();
        }
        // xml data is newer, put it in the db
        $this->putExchangeRates();
        return floatval($this->currencyExchangeRateArray[$currency]);
    }


    /**
     * Get the exchange rates from the website and put them in the database with the timestamp of the data
     * This function should be called in a cron once a day after 14:15; the update time of the exchange rate data
     *
     * @Route("/getexchangerates", name="")
     */
    public function putExchangeRates(): void
    {
        $this->getRates();
        $ratesArray = $this->currencyExchangeRateArray;
        $entityManager = $this->getDoctrine()->getManager();
        foreach($ratesArray as $code => $rate) {
            $exchangeRate = new ExchangeRate();
            $exchangeRate->setCode($code);
            $exchangeRate->setRate($rate);
            $exchangeRate->setTime($this->xmlTime);
            $entityManager->persist($exchangeRate);
            $entityManager->flush();
        }
    }

}
