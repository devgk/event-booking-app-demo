<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test if the events list API is working correctly.
     *
     * @return void
     */
    public function test_list_events_successful()
    {
        // Arrange: Create mock events in the database
        $event1 = Event::create([
            'name' => 'Upcoming Event 1',
            'description' => 'This is an upcoming event.',
            'event_date' => Carbon::tomorrow(), // Future date
            'location_country' => 'England',
            'seats_available' => 100,
			'user_id' => 1
        ]);

        $event2 = Event::create([
            'name' => 'Past Event 1',
            'description' => 'This event has already occurred.',
            'event_date' => Carbon::yesterday(), // Past date
            'location_country' => 'England',
            'seats_available' => 50,
			'user_id' => 1
        ]);

        // Act: Make a request to the listEvents API
        $response = $this->json('POST', '/api/events', [
            'page_number' => 1,
            'event_state' => 'upcoming', // Filtering by upcoming events
        ]);

        // Assert: Check the response structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'events' => [
                        '*' => [
                            'event_id',
                            'event_name',
                            'event_description',
                            'seats_available',
                            'event_date',
                            'event_location_country',
                        ]
                    ],
                    'current_page',
                    'total_pages',
                ],
            ]);

        // Assert: Ensure only upcoming events are returned
        $response->assertJsonFragment([
            'event_name' => 'Upcoming Event 1'
        ]);

        $response->assertJsonMissing([
            'event_name' => 'Past Event 1',
        ]);
    }

    /**
     * Test pagination functionality in the list events API.
     *
     * @return void
     */
    public function test_list_events_pagination()
    {
        // Arrange: Create multiple events
        Event::factory()->count(15)->create();

        // Act: Make a request to the listEvents API with page_number 2
        $response = $this->json('POST', '/api/events', [
            'page_number' => 2,
        ]);

        // Assert: Ensure pagination works
        $response->assertStatus(200)
            ->assertJsonFragment(['current_page' => 2]);
    }

    /**
     * Test validation errors for invalid parameters.
     *
     * @return void
     */
    public function test_list_events_validation_error()
    {
        // Act: Request with missing page_number
        $response = $this->json('POST', '/api/events', [
            'event_state' => 'upcoming',
        ]);

        // Assert: Check for validation error
        $response->assertStatus(422)
                 ->assertJsonFragment(['message' => 'Validation error']);

        // Act: Request with invalid event_state
        $responseInvalidState = $this->json('POST', '/api/events', [
            'page_number' => 1,
            'event_state' => 'invalid_state',
        ]);

        // Assert: Check for validation error
        $responseInvalidState->assertStatus(422)
                              ->assertJsonFragment(['message' => 'Validation error']);
    }
}
