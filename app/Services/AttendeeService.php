<?php

namespace App\Services;

use App\Models\User;
use App\Models\Event;
use App\Models\Booking;
use Illuminate\Support\Str;
use App\Helpers\ApiResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class AttendeeService
{
    /**
     * Book an event for the user.
     *
     * @param  array  $data
     * @return array
     */
    public function bookEvent(array $data)
    {
        // Retrieve the event directly using the event_id
        $event = Event::find($data['event_id']);

        // Check if there are available seats
        $totalBookings = Booking::where('event_id', $event->id)->count();
        if ($totalBookings >= $event->seats_available) {
            return ApiResponse::error('No available seats for this event.', 400);
        }

        // Check if the user already exists in the database
        $user = User::where('email', $data['email'])->first();

        if ($user) {
            // Check if the user has already booked this event
            $booking = Booking::where([
                'user_id' => $user->id,
                'event_id' => $event->id
            ])->first();

            if ($booking) {
				return ApiResponse::error("Event \"{$event->name}\" already booked by {$user->email} on {$booking->booking_date}.", 400);
            }

            if ($user->role_id == 1) {
				return ApiResponse::error("Event cannot be booked by Event Manager (or) Admin.", 400);
            }
        } else {
            // Create a new user if they don't exist
            $user = User::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => Hash::make(Str::random(10)),  // Generate a random password for new users
                'address'   => $data['address'],
                'phone'     => $data['phone'],
            ]);
        }

        // Create the booking for the user
        $booking = Booking::create([
            'user_id'      => $user->id,
            'event_id'     => $event->id,
            'booking_date' => Carbon::now()->toDateString(), // Add the current date
        ]);

        // Return the booking response
        return ApiResponse::success([
            'user_email' => $user->email,
            'event' => $event->name,
            'booking_date' => $booking->booking_date
        ], 'Event booked successfully', 201);
    }

	/**
     * Get a user's booked events.
     *
     * @param  string  $email
     * @param  int  $pageNumber
     * @return array
     */
    public function getMyBookedEvents(string $email, int $pageNumber = 1)
    {
        $perPage = 10; // Define how many events per page

        // Fetch the user based on the provided email
        $user = User::where('email', $email)->first();

        if (!$user) {
            return ApiResponse::error('User not found', 404);
        }

        // Get the paginated booked events for this user
        $bookedEvents = Booking::where('user_id', $user->id)
            ->with('event') // eager load the related event data
            ->paginate($perPage, ['*'], 'page', $pageNumber);

        if ($bookedEvents->isEmpty()) {
            return ApiResponse::error("No events found for user with email: {$email}.", 404);
        }

        // Return the list of events with their names, booking dates, and total pages
        $events = $bookedEvents->map(function ($booking) {
            return [
                'event_name' => $booking->event->name,
                'event_date' => $booking->event->event_date,
                'booking_date' => $booking->booking_date
            ];
        });

        return ApiResponse::success([
            'events' => $events,
            'current_page' => $bookedEvents->currentPage(),
            'total_pages' => $bookedEvents->lastPage(),
        ], 'Booked events fetched successfully');
    }
}
