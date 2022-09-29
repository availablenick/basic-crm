<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_page_can_be_rendered()
    {
        $response = $this->get('/projects');

        $response->assertStatus(200);
    }

    public function test_project_details_page_can_be_rendered()
    {
        $project = Project::factory()
            ->for(Client::factory())
            ->for(User::factory())
            ->create();

        $response = $this->get('/projects/' . $project->id);

        $response->assertStatus(200);
    }

    public function test_logged_out_user_cannot_create_projects()
    {
        $client = Client::factory()->create();
        $user = User::factory()->create();
        $response = $this->post('/projects', [
            'title' => 'test_title',
            'description' => 'test_description',
            'deadline' => '2000-01-01',
            'status' => Project::OPEN_STATUS,
            'company' => $client->company,
            'user_email' => $user->email,
        ]);

        $this->assertDatabaseMissing('projects', [
            'title' => 'test_title',
            'description' => 'test_description',
            'deadline' => '2000-01-01',
            'status' => Project::OPEN_STATUS,
            'client_id' => $client->id,
            'user_id' => $user->id,
        ]);

        $this->assertNull($client->projects()->where('title', 'test_title')->first());
        $this->assertNull($user->projects()->where('title', 'test_title')->first());
        $response->assertRedirect('/login');
    }

    public function test_logged_out_user_cannot_edit_projects()
    {
        $client1 = Client::factory()->create();
        $client2 = Client::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $project = Project::factory()
            ->for($client1)
            ->for($user1)
            ->closed()
            ->create();

        $response = $this->put('/projects/' . $project->id, [
            'title' => 'edit_' . $project->title,
            'description' => 'edit_' . $project->description,
            'deadline' => '2000-01-01',
            'status' => Project::OPEN_STATUS,
            'company' => $client2->company,
            'user_email' => $user2->email,
        ]);

        $this->assertDatabaseMissing('projects', [
            'title' => 'edit_' . $project->title,
            'description' => 'edit_' . $project->description,
            'deadline' => '2000-01-01',
            'status' => Project::OPEN_STATUS,
            'client_id' => $client2->id,
            'user_id' => $user2->id,
        ]);

        $this->assertDatabaseHas('projects', [
            'title' => $project->title,
            'description' => $project->description,
            'deadline' => $project->deadline,
            'status' => $project->status,
            'client_id' => $client1->id,
            'user_id' => $user1->id,
        ]);

        $this->assertNotNull($client1->projects()->where('title', $project->title)->first());
        $this->assertNotNull($user1->projects()->where('title', $project->title)->first());
        $this->assertNull($client2->projects()->where('title', $project->title)->first());
        $this->assertNull($user2->projects()->where('title', $project->title)->first());
        $response->assertRedirect('/login');
    }

    public function test_logged_out_user_cannot_delete_projects()
    {
        $client = Client::factory()->create();
        $user = User::factory()->create();
        $project = Project::factory()
            ->for($client)
            ->for($user)
            ->state(['title' => 'test_title'])
            ->create();

        $response = $this->delete('/projects/' . $project->id);

        $this->assertModelExists($project);
        $this->assertNotNull($client->projects()->where('title', 'test_title')->first());
        $this->assertNotNull($user->projects()->where('title', 'test_title')->first());
        $response->assertRedirect('/login');
    }

    public function test_project_can_be_created()
    {
        $client = Client::factory()->create();
        $user = User::factory()->create();
        $response = $this->login()->post('/projects', [
            'title' => 'test_title',
            'description' => 'test_description',
            'deadline' => '2000-01-01',
            'status' => Project::OPEN_STATUS,
            'company' => $client->company,
            'user_email' => $user->email,
        ]);

        $this->assertDatabaseHas('projects', [
            'title' => 'test_title',
            'description' => 'test_description',
            'deadline' => '2000-01-01',
            'status' => Project::OPEN_STATUS,
            'client_id' => $client->id,
            'user_id' => $user->id,
        ]);

        $this->assertNotNull($client->projects()->where('title', 'test_title')->first());
        $this->assertNotNull($user->projects()->where('title', 'test_title')->first());
        $project = Project::where('title', 'test_title')->first();
        $response->assertRedirect('/projects/'  . $project->id);
    }

    public function test_project_can_be_updated()
    {
        $client1 = Client::factory()->create();
        $client2 = Client::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $project = Project::factory()
            ->for($client1)
            ->for($user1)
            ->closed()
            ->create();

        $response = $this->login()->put('/projects/' . $project->id, [
            'title' => 'edit_' . $project->title,
            'description' => 'edit_' . $project->description,
            'deadline' => '2000-01-01',
            'status' => Project::OPEN_STATUS,
            'company' => $client2->company,
            'user_email' => $user2->email,
        ]);

        $this->assertDatabaseHas('projects', [
            'title' => 'edit_' . $project->title,
            'description' => 'edit_' . $project->description,
            'deadline' => '2000-01-01',
            'status' => Project::OPEN_STATUS,
            'client_id' => $client2->id,
            'user_id' => $user2->id,
        ]);

        $title = 'edit_' . $project->title;
        $this->assertNull($client1->projects()->where('title', $title)->first());
        $this->assertNull($user1->projects()->where('title', $title)->first());
        $this->assertNotNull($client2->projects()->where('title', $title)->first());
        $this->assertNotNull($user2->projects()->where('title', $title)->first());
        $response->assertRedirect('/projects/'  . $project->id);
    }

    public function test_project_can_be_deleted()
    {
        $client = Client::factory()->create();
        $user = User::factory()->create();
        $project = Project::factory()
            ->for($client)
            ->for($user)
            ->state(['title' => 'test_title'])
            ->create();

        $response = $this->login()->delete('/projects/' . $project->id);

        $this->assertModelMissing($project);
        $this->assertNull($client->projects()->where('title', 'test_title')->first());
        $this->assertNull($user->projects()->where('title', 'test_title')->first());
        $response->assertRedirect('/projects');
    }

    /*
        Helpers
    */
    private function login()
	{
		$user = User::factory()->create();
		return $this->actingAs($user);
	}
}