<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity\Currency;

class AppFixtures extends Fixture
{

    private $currencies = [
        ['USD', 'US dollar'],
        ['JPY', 'Japanese yen'],
        ['BGN', 'Bulgarian lev'],
        ['CZK', 'Czech koruna'],
        ['DKK', 'Danish krone'],
        ['GBP', 'Pound sterling'],
        ['HUF', 'Hungarian forint'],
        ['PLN', 'Polish zloty'],
        ['RON', 'Romanian leu'],
        ['SEK', 'Swedish krona'],
        ['CHF', 'Swiss franc'],
        ['ISK', 'Icelandic krona'],
        ['NOK', 'Norwegian krone'],
        ['HRK', 'Croatian kuna'],
        ['RUB', 'Russian rouble'],
        ['TRY', 'Turkish lira'],
        ['AUD', 'Australian dollar'],
        ['BRL', 'Brazilian real'],
        ['CAD', 'Canadian dollar'],
        ['CNY', 'Chinese yuan renminbi'],
        ['HKD', 'Hong Kong dollar'],
        ['IDR', 'Indonesian rupiah'],
        ['ILS', 'Israeli shekel'],
        ['INR', 'Indian rupee'],
        ['KRW', 'South Korean won'],
        ['MXN', 'Mexican peso'],
        ['MYR', 'Malaysian ringgit'],
        ['NZD', 'New Zealand dollar'],
        ['PHP', 'Philippine peso'],
        ['SGD', 'Singapore dollar'],
        ['THB', 'Thai baht'],
        ['ZAR', 'South African rand']
    ];

    public function load(ObjectManager $manager)
    {
        foreach ($this->currencies as $currency) {
            $curr = new Currency();
            $curr->setCode($currency[0]);
            $curr->setName($currency[1]);
            $manager->persist($curr);
        }

        $manager->flush();
    }
}
