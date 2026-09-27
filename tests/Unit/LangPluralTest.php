<?php

namespace Tests\Unit;

use Niang\Core\Lang;
use PHPUnit\Framework\TestCase;

class LangPluralTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Lang::reset();

        $loaded = new \ReflectionProperty(Lang::class, 'loaded');
        $loaded->setValue(null, [
            'fr.panier' => [
                'articles' => 'un article|:count articles',
                'etat' => '{0} Votre panier est vide|{1} Un article dans votre panier|[2,9] :count articles|[10,*] Plus de :count articles, :name !',
                'seul' => ':count élément',
            ],
            'en.panier' => [
                'articles' => 'one item|:count items',
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Lang::reset();
        parent::tearDown();
    }

    public function test_french_treats_zero_and_one_as_singular(): void
    {
        $this->assertSame('un article', Lang::choice('panier.articles', 0));
        $this->assertSame('un article', Lang::choice('panier.articles', 1));
        $this->assertSame('un article', Lang::choice('panier.articles', 1.5));
        $this->assertSame('2 articles', Lang::choice('panier.articles', 2));
    }

    public function test_english_only_treats_one_as_singular(): void
    {
        $this->assertSame('0 items', Lang::choice('panier.articles', 0, [], 'en'));
        $this->assertSame('one item', Lang::choice('panier.articles', 1, [], 'en'));
        $this->assertSame('5 items', Lang::choice('panier.articles', 5, [], 'en'));
    }

    public function test_explicit_values_and_ranges_win(): void
    {
        $this->assertSame('Votre panier est vide', Lang::choice('panier.etat', 0));
        $this->assertSame('Un article dans votre panier', Lang::choice('panier.etat', 1));
        $this->assertSame('9 articles', Lang::choice('panier.etat', 9));
        $this->assertSame('Plus de 250 articles, Awa !', Lang::choice('panier.etat', 250, ['name' => 'Awa']));
    }

    public function test_a_single_form_and_missing_keys(): void
    {
        $this->assertSame('3 élément', Lang::choice('panier.seul', 3));
        $this->assertSame('panier.inconnu', Lang::choice('panier.inconnu', 3));
    }

    public function test_the_helper_and_the_fallback_locale(): void
    {
        Lang::setLocale('en');
        $this->assertSame('2 items', trans_choice('panier.articles', 2));
        $this->assertSame('Votre panier est vide', trans_choice('panier.etat', 0), 'clé absente en anglais : repli sur le français');
    }
}
