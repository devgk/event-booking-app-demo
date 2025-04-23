<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Event;
use App\Models\Booking;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Laravel\Passport\Passport;

class AttendeeControllerTest extends TestCase
{
    // Test for successfully booking an event
    public function test_user_can_book_event()
    {
        // \Create mock events in the database
        $event = Event::create([
            'name' => 'Upcoming Event 1',
            'description' => 'This is an upcoming event.',
            'event_date' => Carbon::tomorrow(), // Future date
            'location_country' => 'England',
            'seats_available' => 100,
			'user_id' => 1
        ]);

        // Send booking request
        $response = $this->postJson('/api/book-event', [
            'name' => 'John Doe',
            'email' => "test@gmail.com",
            'event_id' => $event->id,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'status' => true,
            'message' => 'Event booked successfully',
        ]);
    }

    // Test for event already fully booked
    public function test_event_already_full()
    {
        // Set up user and event with no available seats
        $user = User::factory()->create();
        $event = Event::factory()->create(['seats_available' => 0]);

        // Act as the user
        Passport::actingAs($user);

        // Send booking request
        $response = $this->postJson('/api/book-event', [
            'name' => 'John Doe',
            'email' => $user->email,
            'event_id' => $event->id,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'status' => false,
            'message' => 'No available seats for this event.',
        ]);
    }

    // Test for trying to book event as admin
    public function test_user_is_admin_and_cannot_book_event()
    {
        // Set up an admin user
        $user = User::factory()->create(['role_id' => 1]);
        $event = Event::factory()->create(['seats_available' => 10]);

        // Act as the admin user
        Passport::actingAs($user);

        // Send booking request
        $response = $this->postJson('/api/book-event', [
            'name' => 'Admin User',
            'email' => $user->email,
            'event_id' => $event->id,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'status' => false,
            'message' => "Event cannot be booked by Event Manager (or) Admin.",
        ]);
    }

    // Test for new user booking an event
    public function test_new_user_can_book_event()
    {
        $user = User::factory()->make(); // This is an unsaved user
        $event = Event::factory()->create(['seats_available' => 10]);

        // Act as the new user
        Passport::actingAs($user);

        // Send booking request
        $response = $this->postJson('/api/book-event', [
            'name' => $user->name,
            'email' => $user->email,
            'event_id' => $event->id,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'status' => true,
            'message' => 'Event booked successfully',
        ]);
    }

    // Test for fetching booked events successfully
    public function test_user_can_fetch_booked_events()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();
        Booking::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'booking_date' => now(),
        ]);

        // Act as the user
        Passport::actingAs($user);

        // Send request to fetch booked events
        $response = $this->postJson('/api/get-my-booked-events', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'message' => 'Booked events fetched successfully',
        ]);
    }

    // Test for no events booked by user
    public function test_user_has_no_booked_events()
    {
        $user = User::factory()->create();

        // Act as the user
        Passport::actingAs($user);

        // Send request to fetch booked events
        $response = $this->postJson('/api/get-my-booked-events', [
            'email' => $user->email,
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'status' => false,
            'message' => "No events found for user with email: {$user->email}.",
        ]);
    }

    // Test for validation errors when email is missing
    public function test_fetch_booked_events_validation_error()
    {
        $response = $this->postJson('/api/get-my-booked-events', [
            'email' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => false,
            'message' => 'Validation error',
        ]);
    }
}
