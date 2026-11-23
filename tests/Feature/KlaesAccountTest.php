<?php

namespace Tests\Feature;

use LogicException;
use Tests\Support\KlaesUsers;
use Tests\TestCase;

/**
 * KLAES accounts are read-only here: shown on My account, never changed, no self-service routes.
 */
class KlaesAccountTest extends TestCase
{
    use KlaesUsers;

    public function test_my_account_shows_the_klaes_details_read_only(): void
    {
        $user = $this->klaesUser(['rank' => 'Principal Officer']);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertSee('Amina Bello');
        $response->assertSee('abello');
        $response->assertSee('Principal Officer');
        $response->assertDontSee('action="'.url('/profile').'"', false);
        $response->assertDontSee('name="password"', false);
    }

    public function test_the_dashboard_greets_the_user_by_klaes_name(): void
    {
        $this->actingAs($this->klaesUser())->get('/dashboard')->assertOk()->assertSee('Amina Bello');
    }

    public function test_there_is_no_way_to_change_or_create_an_account_here(): void
    {
        $user = $this->klaesUser();

        $this->actingAs($user)->patch('/profile')->assertStatus(405);
        $this->actingAs($user)->delete('/profile')->assertStatus(405);
        $this->actingAs($user)->put('/password')->assertNotFound();

        auth()->logout();
        $this->get('/register')->assertNotFound();
        $this->get('/forgot-password')->assertNotFound();
    }

    public function test_the_user_model_refuses_to_save(): void
    {
        $user = $this->klaesUser();
        $user->first_name = 'Changed';

        $this->expectException(LogicException::class);

        $user->save();
    }

    public function test_the_user_model_refuses_to_delete(): void
    {
        $this->expectException(LogicException::class);

        $this->klaesUser()->delete();
    }
}
