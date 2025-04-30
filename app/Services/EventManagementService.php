<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Booking;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class EventManagementService
{
    /**
     * List all events with pagination and state filter.
     *
     * @param array $filters
     * @param int $pageNumber
     */
    public function listEvents(array $filters, int $pageNumber)
    {
		// Default to null if not provided
        $location_country = $filters['location_country'] ?? null;
		// Default to 'upcoming' if not provided
        $eventState = $filters['event_state'] ?? 'upcoming';

        // Define how many events per page (fixed at 10)
        $perPage = 10;

        // Get the events query
        $eventsQuery = Event::query();

        // Filter by location if provided
        if ($location_country) {
            $eventsQuery->where('location_country', strtoupper($location_country));
        }

        // Filter by event_state
        $today = Carbon::today(); // Get today's date
        if ($eventState == 'upcoming') {
            $eventsQuery->where('event_date', '>=', $today); // Show events today or in the future
        } elseif ($eventState == 'past') {
            $eventsQuery->where('event_date', '<', $today); // Show past events
        }

        // Fetch the paginated events
        $events = $eventsQuery->paginate($perPage, ['*'], 'page', $pageNumber);

		// Format the event data for the response
		$event_list = $events->map(function ($event) {
			return [
				"event_id" => $event->id,
				"event_name" => $event->name,
				"event_description" => $event->description,
				"seats_available" => $event->seats_available,
				"event_date" => $event->event_date,
				"event_location_country" => $event->location_country,
			];
		});

		// Return event list with pagination data
		return [
			'events' => $event_list,
            'current_page' => $events->currentPage(),
            'total_pages' => $events->lastPage(),
		];
    }

    /**
     * View event details including total bookings.
     *
     * @param int $id
     * @return array|null
     */
    public function viewEvent(int $id)
    {
        // Find Event
        $event = Event::find($id);

        if (!$event) {
            return null;
        }

        // Get Total Bookings
        $total_bookings = $event->bookings()->count();

		// Return required event details
        return [
            'event_name' => $event->name,
            'event_description' => $event->description,
            'event_date' => $event->event_date,
            'event_location_country' => $event->location_country,
            'seats_available' => $event->seats_available,
            'seats_booked' => $total_bookings,
        ];
    }

    /**
     * Create a new event.
     *
     * @param array $data
     * @param int $userId
     */
    public function createEvent(array $data, int $userId)
    {
        // Create Event
        $new_event = Event::create([
            'name' => $data['name'],
            'description' => $data['description'],
            'event_date' => $data['event_date'],
            'seats_available' => $data['seats_available'],
            'location_country' => strtoupper($data['location_country']),
            'user_id' => $userId,
        ]);

		// Return required event details
		return [
			"id" => $new_event->id,
			"name" => $new_event->name,
			"description" => $new_event->description,
			"event_date" => $new_event->new_event_date,
			"seats_available" => $new_event->seats_available,
			"location_country" => $new_event->location_country,
		];
    }

    /**
     * Update an event.
     *
     * @param int $id
     * @param array $data
     */
    public function updateEvent(int $id, array $data)
    {
        // Find Event
        $event = Event::find($id);

        if (!$event) {
            return null;
        }

        // Update event
        $event->update([
            'name' => $data['name'],
            'description' => $data['description'],
            'event_date' => $data['event_date'],
            'location_country' => $data['location_country'],
            'seats_available' => $data['seats_available'],
        ]);

		// Return required event details
        return [
			"id" => $event->id,
			"name" => $event->name,
			"description" => $event->description,
			"event_date" => $event->event_date,
			"location_country" => $event->location_country,
			"seats_available" => $event->seats_available,
		];
    }

    /**
     * Delete an event.
     *
     * @param int $id
     * @return bool
     */
    public function deleteEvent(int $id)
    {
        // Find Event
        $event = Event::find($id);

        if (!$event) {
            return false;
        }

        // Delete the event
        $event->delete();

        return true;
    }
}
