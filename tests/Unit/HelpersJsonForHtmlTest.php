<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * json_for_html() est sensible en sécurité (XSS) : chaque cas ci-dessous vérifie un vecteur réel,
 * en simulant le cycle complet — le résultat n'est PAS du JSON directement décodable (il est
 * htmlspecialchars()-é en plus des flags JSON_HEX_*, voir sa docblock), mais du texte prêt à être
 * inséré tel quel dans un attribut HTML : chaque test construit ce HTML, le fait relire par un
 * vrai parseur (qui décode les entités comme le ferait un navigateur), puis json_decode() le
 * résultat — jamais json_decode() directement sur la sortie de json_for_html().
 */
class HelpersJsonForHtmlTest extends TestCase
{
    public function test_encodes_a_simple_array_as_valid_json(): void
    {
        $this->assertSame(['name' => 'Awa', 'age' => 30], $this->roundTrip(['name' => 'Awa', 'age' => 30]));
    }

    public function test_escapes_a_closing_script_tag_so_it_cannot_break_out_of_a_script_element(): void
    {
        $json = json_for_html(['payload' => '</script><script>alert(1)</script>']);

        $this->assertStringNotContainsString('</script>', $json);
        $this->assertSame(
            ['payload' => '</script><script>alert(1)</script>'],
            $this->roundTrip(['payload' => '</script><script>alert(1)</script>'])
        );
    }

    public function test_escapes_double_quotes_so_they_cannot_break_out_of_a_double_quoted_attribute(): void
    {
        // Le vrai vecteur XSS que ce helper existe pour empêcher : un objet PHP encodé produit
        // des guillemets STRUCTURELS ({"payload":"...") en plus de ceux du contenu — sans la
        // couche htmlspecialchars(), ces guillemets structurels referment l'attribut dès le
        // premier `"` rencontré (vérifié avec un vrai Chromium en écrivant ce test).
        $data = ['payload' => 'valeur" onmouseover="alert(1)'];
        $json = json_for_html($data);
        $html = '<div id="target" data-props="' . $json . '"></div>';

        $dom = new \DOMDocument();
        $dom->loadHTML('<meta charset="utf-8">' . $html);
        $div = $dom->getElementById('target');

        $this->assertNotNull($div);
        $this->assertFalse($div->hasAttribute('onmouseover'));
        $this->assertSame($data, json_decode($div->getAttribute('data-props'), true));
    }

    public function test_escapes_single_quotes_so_they_cannot_break_out_of_a_single_quoted_attribute(): void
    {
        $data = ['payload' => "valeur' onmouseover='alert(1)"];

        $this->assertSame($data, $this->roundTrip($data));
    }

    public function test_escapes_ampersands_to_avoid_accidental_html_entities(): void
    {
        $data = ['payload' => 'Awa & Fatou'];

        $this->assertSame($data, $this->roundTrip($data));
    }

    public function test_round_trips_unicode_characters_safely(): void
    {
        $data = ['payload' => 'Ndiaye — café à Dakar 😊'];

        $this->assertSame($data, $this->roundTrip($data));
    }

    public function test_is_safe_to_embed_directly_in_a_double_quoted_html_attribute(): void
    {
        // Combine guillemet ET balise fermante dans la même valeur, sur un tableau (pas une
        // simple string) : le cas d'usage réellement documenté (<div data-props="...">).
        $data = ['payload' => '"></div><script>alert(document.cookie)</script>'];

        $this->assertSame($data, $this->roundTrip($data));
    }

    public function test_a_json_encoding_failure_throws_a_clear_exception(): void
    {
        $this->expectException(\JsonException::class);

        // NAN n'est pas représentable en JSON : json_encode() échoue (false), sans exception PHP
        // native à laisser fuir telle quelle.
        json_for_html(NAN);
    }

    /** Construit le HTML tel que documenté, le fait reparser (décodage des entités), puis json_decode(). */
    private function roundTrip(mixed $data): mixed
    {
        $json = json_for_html($data);
        $html = '<div id="target" data-props="' . $json . '"></div>';

        $dom = new \DOMDocument();
        $dom->loadHTML('<meta charset="utf-8">' . $html);
        $div = $dom->getElementById('target');

        return json_decode($div->getAttribute('data-props'), true);
    }
}
