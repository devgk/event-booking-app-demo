<?php

namespace App\Http\Controllers\api;

use App\Models\User;
use App\Models\Event;
use App\Models\Booking;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AttendeeController extends Controller
{
	/**
	 * @OA\Post(
	 *     tags={"Attendees APIs"},
	 *     path="/api/book-event",
	 *     operationId="bookEvent",
	 *     summary="Book an event for an attendee",
	 *     description="This API allows an attendee to book an event by providing details such as name, email, and event ID.",
	 *     @OA\RequestBody(
	 *         required=true,
	 *         description="Booking details",
	 *         @OA\MediaType(
	 *             mediaType="application/json",
	 *             @OA\Schema(
	 *                 type="object",
	 *                 required={"name", "email", "event_id"},
	 *                 @OA\Property(property="name", type="string", example="John Doe", description="The name of the attendee."),
	 *                 @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="The email address of the attendee."),
	 *                 @OA\Property(property="event_id", type="integer", example=1, description="The ID of the event being booked."),
	 *                 @OA\Property(property="address", type="string", example="1234 Elm Street", description="The address of the attendee (optional)."),
	 *                 @OA\Property(property="phone", type="string", example="1234567890", description="The phone number of the attendee (optional).")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=201,
	 *         description="Event successfully booked",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Event booked successfully"),
	 *             @OA\Property(property="data", type="object",
	 *                 @OA\Property(property="user_email", type="string", example="john.doe@example.com"),
	 *                 @OA\Property(property="event", type="string", example="Tech Conference"),
	 *                 @OA\Property(property="booking_date", type="string", format="date", example="2025-04-22")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=400,
	 *         description="Bad request - validation error or event already booked",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Event ABC already booked by john.doe@example.com on 2025-04-22.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=422,
	 *         description="Validation error - required fields missing or invalid",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Validation error"),
	 *             @OA\Property(property="errors", type="object",
	 *                 @OA\Property(property="name", type="array", items=@OA\Items(type="string", example="The name field is required.")),
	 *                 @OA\Property(property="email", type="array", items=@OA\Items(type="string", example="The email field is required.")),
	 *                 @OA\Property(property="event_id", type="array", items=@OA\Items(type="string", example="The event_id field is required."))
	 *             )
	 *         )
	 *     )
	 * )
	 */
	public function bookEvent(Request $request)
	{
        // Validate input
        $validator = Validator::make($request->all(), [
            'name'      => 'required|string|max:255',
            'email'     => 'required|email',
            'event_id'  => 'required|integer|exists:events,id',
            'address'   => 'nullable|string|max:255',
            'phone'     => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Retrieve the event directly using the validated event_id
        $event = Event::find($request->event_id);

		// Check if there are available seats
		$totalBookings = Booking::where('event_id', $event->id)->count();
		if ($totalBookings >= $event->seats_available) {
			return response()->json([
				'status' => false,
				'message' => 'No available seats for this event.',
			], 400);
		}

        // Check if the user already exists in the database
        $user = User::where('email', $request->email)->first();

        if ($user) {
            // Check if the user has already booked this event
            $booking = Booking::where([
                'user_id' => $user->id,
                'event_id' => $event->id
            ])->first();

            if ($booking) {
                return response()->json([
                    'status' => false,
                    'message' => "Event \"{$event->name}\" already booked by {$user->email} on {$booking->booking_date}.",
                ], 400);
            }

			if ($user->role_id == 1) {
                return response()->json([
                    'status' => false,
                    'message' => "Event cannot be booked by Event Manager (or) Admin.",
                ], 400);
            }
        } else {
            // Create a new user if they don't exist
            $user = User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => Hash::make(Str::random(10)),  // Generate a random password for new users
                'address'   => $request->address,
                'phone'     => $request->phone,
            ]);
        }

        // Create the booking for the user
        $booking = Booking::create([
            'user_id'      => $user->id,
            'event_id'     => $event->id,
            'booking_date' => Carbon::now()->toDateString(), // Add the current date
        ]);

        // Return success response
        return response()->json([
            'status' => true,
            'message' => 'Event booked successfully',
            'data' => [
                'user_email' => $user->email,
                'event' => $event->name,
                'booking_date' => $booking->booking_date
            ]
        ], 201);
    }

	/**
	 * @OA\Post(
	 *     tags={"Attendees APIs"},
	 *     path="/api/get-my-booked-events",
	 *     operationId="getMyBookedEvents",
	 *     summary="Get a user's booked events",
	 *     description="This API allows a user to get their booked events by providing their email and optionally a page number for pagination.",
	 *     @OA\RequestBody(
	 *         required=true,
	 *         description="The user's email and page number for pagination",
	 *         @OA\MediaType(
	 *             mediaType="application/json",
	 *             @OA\Schema(
	 *                 type="object",
	 *                 required={"email"},
	 *                 @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="The email address of the user."),
	 *                 @OA\Property(property="page_number", type="integer", example=1, description="The page number for pagination (optional).")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Booked events fetched successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Booked events fetched successfully"),
	 *             @OA\Property(property="data", type="object",
	 *                 @OA\Property(property="events", type="array", 
	 *                     @OA\Items(
	 *                         type="object",
	 *                         @OA\Property(property="event_name", type="string", example="Tech Conference 2025"),
	 *                         @OA\Property(property="event_date", type="string", format="date", example="2025-12-01"),
	 *                         @OA\Property(property="booking_date", type="string", format="date", example="2025-04-22")
	 *                     )
	 *                 ),
	 *                 @OA\Property(property="current_page", type="integer", example=1),
	 *                 @OA\Property(property="total_pages", type="integer", example=2)
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No events found for the user",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No events found for user with email: john.doe@example.com.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=422,
	 *         description="Validation error - invalid email or page number",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Validation error"),
	 *             @OA\Property(property="errors", type="object",
	 *                 @OA\Property(property="email", type="array", items=@OA\Items(type="string", example="The email field is required.")),
	 *                 @OA\Property(property="page_number", type="array", items=@OA\Items(type="string", example="The page number must be at least 1."))
	 *             )
	 *         )
	 *     )
	 * )
	 */
	public function getMyBookedEvents(Request $request)
	{
		// Validate the provided email and page_number
		$validator = Validator::make($request->all(), [
			'email' => 'required|email',
			'page_number' => 'nullable|integer|min:1',
		]);

		if ($validator->fails()) {
			return response()->json([
				'status' => false,
				'message' => 'Validation error',
				'errors' => $validator->errors()
			], 422);
		}

		// Fetch the user based on the provided email
		$user = User::where('email', $request->email)->first();

		if (!$user) {
			return response()->json([
				'status' => false,
				'message' => 'User not found',
			], 404);
		}

		// Set the page number and per page
		$pageNumber = $request->page_number ?: 1; // Default to 1 if no page_number is provided
		$perPage = 10; // Define how many events per page

		// Get the paginated booked events for this user
		$bookedEvents = Booking::where('user_id', $user->id)
			->with('event') // eager load the related event data
			->paginate($perPage, ['*'], 'page', $pageNumber);

		// If there are no bookings, return a custom message
		if ($bookedEvents->isEmpty()) {
			return response()->json([
				'status' => false,
				'message' => "No events found for user with email: {$request->email}.",
			], 404);
		}

		// Return the list of events with their names, booking dates, and total pages
		$events = $bookedEvents->map(function ($booking) {
			return [
				'event_name' => $booking->event->name,
				'event_date' => $booking->event->event_date,
				'booking_date' => $booking->booking_date
			];
		});

		return response()->json([
			'status' => true,
			'message' => 'Booked events fetched successfully',
			'data' => [
				'events' => $events,
				'current_page' => $bookedEvents->currentPage(),
				'total_pages' => $bookedEvents->lastPage(),
			]
		], 200);
	}
}
