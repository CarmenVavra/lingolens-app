<?php

namespace Tests\Feature;

use App\Enums\ErrorType;
use App\Models\Scan;
use App\Models\User;
use App\Services\DashboardStatistics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_students_see_an_empty_dashboard(): void
    {
        $user = User::factory()->create(['name' => 'Lena Gruber']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertSee('Servus, Lena!')
            ->assertSee('Neuen Text scannen')
            ->assertSee('Noch keine Korrekturen.')
            ->assertSee('0 Korrekturen aus 0 Scans');
    }

    public function test_dashboard_shows_error_distribution_score_focus_and_latest_scans(): void
    {
        $user = User::factory()->create();
        $scan = $this->completedScan($user);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200)
            ->assertSee('11 Korrekturen aus 1 Scans')
            ->assertSeeInOrder(['Fachbegriff', 'Grammatik', 'Stil', 'Vokabular'])
            ->assertSee('Fachbegriffe statt Alltagssprache')
            ->assertSee('Größter Hebel: Fachbegriffe.')
            ->assertSee(route('scans.show', ['scan' => $scan->id, 'type' => 'Technical Term']), false)
            ->assertSee('Lab Report: Distillation of an Ethanol–Water Mixture')
            ->assertSee('BHS · HTL');
    }

    public function test_error_statistics_only_count_the_students_own_corrections(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->completedScan($user);
        $this->completedScan($other);
        $this->completedScan($other, 'Essay');

        $stats = app(DashboardStatistics::class)->errorStats($user);

        $this->assertSame(11, (int) $stats->sum('total'));
        $this->assertSame(ErrorType::TechnicalTerm, $stats->first()->error_type);
        $this->assertSame(4, (int) $stats->first()->getAttribute('total'));
    }

    public function test_dashboard_lists_only_the_three_latest_scans(): void
    {
        $user = User::factory()->create();

        foreach (['Alpha', 'Beta', 'Gamma', 'Delta'] as $index => $title) {
            Scan::factory()->for($user)->failed()->create([
                'title' => $title.' Text',
                'created_at' => now()->subDays(4 - $index),
            ]);
        }

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSee('Delta Text')
            ->assertSee('Beta Text')
            ->assertDontSee('Alpha Text');
    }
}
