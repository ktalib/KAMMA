<?php

namespace Tests\Feature\Bpa;

use App\Support\Bpa\BpaTemplates;
use Tests\Support\KlaesUsers;
use Tests\TestCase;

/** The Building Plan Approval print templates (KNUPDA paper forms under the KAMMA name): blank and with sample data. */
class TemplateTest extends TestCase
{
    use KlaesUsers;

    public function test_the_templates_need_a_sign_in(): void
    {
        $this->get(route('bpa.templates.index'))->assertRedirect(route('login'));
        $this->get(route('bpa.templates.show', 'checklist'))->assertRedirect(route('login'));
    }

    public function test_the_index_lists_every_template(): void
    {
        $response = $this->actingAs($this->klaesUser())->get(route('bpa.templates.index'))->assertOk();

        foreach (BpaTemplates::LIST as [$title]) {
            $response->assertSee($title);
        }
    }

    public function test_every_template_prints_blank_and_with_sample_data_under_the_kadca_name(): void
    {
        $user = $this->klaesUser();

        foreach (array_keys(BpaTemplates::LIST) as $slug) {
            foreach ([false, true] as $sample) {
                $html = $this->actingAs($user)->get(route('bpa.templates.show', $sample ? [$slug, 'sample' => 1] : $slug))
                    ->assertOk()->getContent();

                $this->assertStringNotContainsStringIgnoringCase('knupda', $html, $slug);
                $this->assertStringNotContainsStringIgnoringCase('Urban Planning', $html, $slug);
                if ($slug === 'kamma-recommendation-letterhead') {
                    // For KAMMA's printed letterhead paper: no header of ours at all (user 2026-11-24).
                    $this->assertStringNotContainsString('images/kamma-seal.png', $html, $slug);
                    $this->assertStringNotContainsString('images/klaes-metro-logo.png', $html, $slug);
                    $this->assertStringNotContainsString('class="qr-block"', $html, $slug);
                    $this->assertStringContainsString('class="letterhead-space"', $html, $slug);
                } elseif (in_array($slug, ['memo', 'conveyance', 'kamma-recommendation'], true)) {
                    // QR + security code in the KLAES-METRO logo's place (user 2026-11-24, as on the Land RofO).
                    $this->assertStringNotContainsString('images/klaes-metro-logo.png', $html, $slug);
                    $this->assertStringContainsString($sample ? '<svg' : 'qr-blank', $html, $slug.': QR');
                    $this->assertStringContainsString($sample ? 'class="frac"' : 'sec-blank', $html, $slug.': security code');
                } else {
                    $this->assertStringContainsString('images/klaes-metro-logo.png', $html, $slug.': KLAES-METRO logo beside the seal');
                }
                if ($slug !== 'kamma-recommendation-letterhead') {
                    $this->assertStringContainsString('images/kamma-seal.png', $html, $slug.': KAMMA seal');
                }
                $this->assertSame(1, substr_count($html, 'images/klaes-logo.png'), $slug.': KLAES logo once, on the last sheet');
            }
        }
    }

    public function test_a_blank_template_prints_no_values_and_the_sample_fills_them(): void
    {
        $user = $this->klaesUser();

        $this->actingAs($user)->get(route('bpa.templates.show', 'application-form'))->assertOk()
            ->assertSee('Kano State Metropolitan Area Management Agency')->assertDontSee('Development Control Agency')
            ->assertDontSee('AMINU SANI DANLADI');

        $this->actingAs($user)->get(route('bpa.templates.show', ['application-form', 'sample' => 1]))->assertOk()
            ->assertSee('AMINU SANI DANLADI')
            ->assertSee('BP-2026-1');

        $this->actingAs($user)->get(route('bpa.templates.show', ['conveyance', 'sample' => 1]))->assertOk()
            ->assertSee('N50,000')->assertSee('N100,000')
            ->assertSee('xxiv.');

        $this->actingAs($user)->get(route('bpa.templates.show', 'memo'))->assertOk()
            ->assertSee('Physical Planning Department')->assertSee('Director Physical Planning')
            ->assertDontSee('CON-RES-2026-101');

        $this->actingAs($user)->get(route('bpa.templates.show', ['memo', 'sample' => 1]))->assertOk()
            ->assertSee('CON-RES-2026-101')->assertSee('for a', false)->assertSee('residential')
            ->assertSee('subject to payment of betterment fee');

        $this->actingAs($user)->get(route('bpa.templates.show', 'kamma-recommendation'))->assertOk()
            ->assertSee('The Honourable Commissioner')->assertSee('Director Physical Planning')->assertSee('Managing Director')
            ->assertDontSee('TPL. HAUWA BELLO');

        $this->actingAs($user)->get(route('bpa.templates.show', ['kamma-recommendation', 'sample' => 1]))->assertOk()
            ->assertSee('TPL. HAUWA BELLO')->assertSee('20.00m x 30.00m x 20.00m x 30.00m');
    }

    public function test_an_unknown_template_is_not_found(): void
    {
        $this->actingAs($this->klaesUser())->get('/development-permit/templates/nothing-here')->assertNotFound();
    }
}
