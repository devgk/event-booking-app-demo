<?php

namespace App\Http\Controllers\api;

use App\Models\User;
use App\Models\Event;
use App\Models\Booking;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Services\AttendeeService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\BookEventRequest;
use Illuminate\Support\Facades\Validator;

class AttendeeController extends Controller
{
	protected $attendeeService;

    // Inject AttendeeService into the controller
    public function __construct(AttendeeService $attendeeService)
    {
        $this->attendeeService = $attendeeService;
    }

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
	public function bookEvent(BookEventRequest $request)
	{
		// Call the service method to handle event booking
		$response = $this->attendeeService->bookEvent($request->validated());

		return $response;
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
		// Call the service method to fetch booked events
		$response = $this->attendeeService->getMyBookedEvents($request->email, $request->page_number);

		return $response;
	}
}
