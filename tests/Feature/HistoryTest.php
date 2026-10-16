<?php

namespace Tests\Feature;

use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_lists_own_scans_with_status_and_links(): void
    {
        $user = User::factory()->create();
        $completed = $this->completedScan($user);
        Scan::factory()->for($user)->failed()->create(['title' => 'Water Analysis']);
        Scan::factory()->for($user)->create(['title' => 'Internship Application', 'text_type' => 'Formal Letter']);
        Scan::factory()->for(User::factory())->failed()->create(['title' => 'Fremder Scan']);

        $this->actingAs($user)
            ->get(route('scans.index'))
            ->assertOk()
            ->assertSee('Meine Scans')
            ->assertSee('Technical Report · 11 Korrekturen · Heute,', false)
            ->assertSee(route('scans.show', $completed), false)
            ->assertSee('Fertig')
            ->assertSee('Fehlgeschlagen')
            ->assertSee('Wird analysiert')
            ->assertSee('Internship Application')
            ->assertDontSee('Fremder Scan');
    }

    public function test_history_shows_an_empty_state(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('scans.index'))
            ->assertOk()
            ->assertSee('Noch keine Scans.');
    }
}
