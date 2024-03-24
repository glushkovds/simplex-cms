<?php

class FirstCest
{
    public function frontpageWorks(AcceptanceTester $I)
    {
        $I->amOnPage('/');
        $I->see('Актив Финанс групп');
        $I->see('Спецтехника в Перми', 'h1');
    }
}
