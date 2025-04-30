<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Event;
use App\Models\Booking;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use App\Rules\LocationCountry;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\EventManagementService;
use App\Http\Requests\CreateEventRequest;
use App\Http\Requests\UpdateEventRequest;
use Illuminate\Support\Facades\Validator;

class EventManagementController extends Controller
{
	protected $eventManagementService;

	// Implementing Application Logic
    public function __construct(EventManagementService $eventManagementService)
    {
		// Creating the object of event management service class
		// which can be used in the controller as required
        $this->eventManagementService = $eventManagementService;
    }

    /**
	 * @OA\Post(
	 *     tags={"Events - Event Management"},
	 *     path="/api/events",
	 *     operationId="listEvents",
	 *     summary="List all events with pagination and state filter",
	 *     description="Get a list of all events with pagination and event state filtering support.",
	 *     @OA\RequestBody(
	 *         required=true,
	 *         description="Pagination and event state filter data",
	 *         @OA\MediaType(
	 *             mediaType="application/json",
	 *             @OA\Schema(
	 *                 type="object",
	 *                 required={"page_number"},
	 *                 @OA\Property(property="page_number", type="integer", example=1, description="The page number to fetch"),
	 *                 @OA\Property(property="location_country", type="string", example="England", description="The location to filter events by country"),
	 *                 @OA\Property(property="event_state", type="string", example="upcoming", description="State of the event (upcoming, past)")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="List of events",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Event list fetched successfully"),
	 *             @OA\Property(property="data", type="object",
	 *                 @OA\Property(property="events", type="array",
	 *                     @OA\Items(
	 *                         type="object",
	 *                         @OA\Property(property="id", type="integer", example=1),
	 *                         @OA\Property(property="name", type="string", example="Demo Event"),
	 *                         @OA\Property(property="description", type="string", example="This is demo event description"),
	 *                         @OA\Property(property="event_date", type="string", format="date", example="2025-12-25"),
	 *                         @OA\Property(property="location_country", type="string", example="England")
	 *                     )
	 *                 ),
	 *                 @OA\Property(property="current_page", type="integer", example=1),
	 *                 @OA\Property(property="total_pages", type="integer", example=5)
	 *             )
	 *         )
	 *     ),
	 * )
	 */
	public function listEvents(Request $request)
	{
		// Validate the provided page_number, location, and event_state
		$validator = Validator::make($request->all(), [
			'page_number' => 'required|integer|min:1',
			'location_country' => ['required', 'string', new LocationCountry],
			'event_state' => 'nullable|in:upcoming,past',
		]);

		// If validation fails, return a response with the validation errors
		if ($validator->fails()) {
			return ApiResponse::error('Validation error', 422, $validator->errors()->all());
		}

		// Get page number from the request
		$pageNumber = $request->page_number;
		// Creating filter array from request
        $filters = $request->only(['location_country', 'event_state']);
		// Get events list as per the filter and page number provided
        $event_list = $this->eventManagementService->listEvents($filters, $pageNumber);

		// Return paginated response
        return ApiResponse::success($event_list, 'Event list fetched successfully');
	}

	/**
	 * @OA\Get(
	 *     tags={"Events - Event Management"},
	 *     path="/api/event/{id}",
	 *     operationId="viewEvent",
	 *     summary="View event details along with total bookings",
	 *     description="Get the details of a specific event, including the event name, description, date, location, available seats, and total bookings.",
	 *     @OA\Parameter(
	 *         name="id",
	 *         in="path",
	 *         required=true,
	 *         description="The ID of the event to fetch",
	 *         @OA\Schema(type="integer")
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Event details fetched successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Event details fetched successfully"),
	 *             @OA\Property(property="data", type="object",
	 *                 @OA\Property(property="name", type="string", example="Demo Event"),
	 *                 @OA\Property(property="description", type="string", example="This is demo event description"),
	 *                 @OA\Property(property="event_date", type="string", format="date", example="2025-12-25"),
	 *                 @OA\Property(property="event_location_country", type="string", example="England"),
	 *                 @OA\Property(property="seats_available", type="integer", example=100),
	 *                 @OA\Property(property="seats_booked", type="integer", example=50)
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="Event not found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Event not found")
	 *         )
	 *     )
	 * )
	 */
	public function viewEvent($id, Request $request)
	{
		// Validate that $id is a valid integer
		if (!is_numeric($id) || (int)$id != $id) {
			return ApiResponse::error('Invalid event ID', 400);
		}

		// Get Events as per the id
		$event = $this->eventManagementService->viewEvent($id);

		// Return error if event not found
		if (!$event) {
			return ApiResponse::error('Event not found', 404);
        }

		// Return success with event details
		return ApiResponse::success($event, 'Event details fetched successfully');
	}

	/**
	 * @OA\Post(
	 *     tags={"Events - Event Management"},
	 *     path="/api/manage/event",
	 *     operationId="createEvent",
	 *     summary="Create a new event",
	 *     description="Create a new event by passing raw JSON and Bearer token in the header.",
     *     security={{"bearerAuth":{}}},
	 *     @OA\RequestBody(
	 *         required=true,
	 *         description="Event data to be created",
	 *         @OA\MediaType(
	 *             mediaType="application/json",
	 *             @OA\Schema(
	 *                 type="object",
	 *                 required={"name", "event_date", "seats_available", "location_country"},
	 *                 @OA\Property(property="name", type="string", example="Demo Event"),
	 *                 @OA\Property(property="description", type="string", example="This is demo event description"),
	 *                 @OA\Property(property="event_date", type="string", format="date", example="2025-12-25"),
	 *                 @OA\Property(property="seats_available", type="integer", example=100),
	 *                 @OA\Property(property="location_country", type="string", example="England")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=201,
	 *         description="Event created successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Event created successfully"),
	 *             @OA\Property(property="data", type="object",
	 *                 @OA\Property(property="id", type="integer", example=1),
	 *                 @OA\Property(property="name", type="string", example="Demo Event"),
	 *                 @OA\Property(property="description", type="string", example="This is demo event description"),
	 *                 @OA\Property(property="event_date", type="string", format="date", example="2025-12-25"),
	 *                 @OA\Property(property="seats_available", type="integer", example=100),
	 *                 @OA\Property(property="location_country", type="string", example="England")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=400,
	 *         description="Bad request, invalid data",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Bad Request")
	 *         )
	 *     ),
	 * )
	 */
	public function createEvent(CreateEventRequest $request)
	{
		// Get the current user's ID (authenticated via API token)
        $userId = Auth::guard('api')->user()->id;

		// Create Event
        $new_event = $this->eventManagementService->createEvent($request->all(), $userId);

		// Return Success Response
        return ApiResponse::success($new_event, 'Event created successfully', 201);
	}

	/**
	 * @OA\Put(
	 *     tags={"Events - Event Management"},
	 *     path="/api/manage/event/{id}",
	 *     operationId="updateEvent",
	 *     summary="Update an event",
	 *     description="Update event details",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\Parameter(
	 *         name="id",
	 *         in="path",
	 *         required=true,
	 *         description="Event ID",
	 *         @OA\Schema(type="integer", example=1),
	 *     ),
	 *     @OA\RequestBody(
	 *         required=true,
	 *         description="Event data to be Updated",
	 *         @OA\MediaType(
	 *             mediaType="application/json",
	 *             @OA\Schema(
	 *                 type="object",
	 *                 required={"name", "event_date", "location", "seats_available"},
	 *                 @OA\Property(property="name", type="string", example="Demo Event"),
	 *                 @OA\Property(property="description", type="string", example="This is demo event description"),
	 *                 @OA\Property(property="event_date", type="string", format="date", example="2025-12-25"),
	 *                 @OA\Property(property="seats_available", type="integer", example=100),
	 *                 @OA\Property(property="location_country", type="string", example="England")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Event updated successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Event updated successfully."),
	 *             @OA\Property(property="data", type="object",
	 *                 @OA\Property(property="id", type="integer", example=1),
	 *                 @OA\Property(property="name", type="string", example="Demo Event"),
	 *                 @OA\Property(property="description", type="string", example="This is demo event description"),
	 *                 @OA\Property(property="event_date", type="string", format="date", example="2025-12-25"),
	 *                 @OA\Property(property="seats_available", type="integer", example=100),
	 *                 @OA\Property(property="location_country", type="string", example="England")
	 *             )
	 *         ),
	 *     ),
	 * )
	 */
	public function updateEvent($id, UpdateEventRequest $request)
	{
		// Update the event with the validated data
		$event = $this->eventManagementService->updateEvent($id, $request->all());

		// Return error if event not found & updated
        if (!$event) {
            return ApiResponse::error('Event not found', 404);
        }

		// Return Success Response
        return ApiResponse::success($event, 'Event updated successfully');
	}

	/**
	 * @OA\Delete(
	 *     tags={"Events - Event Management"},
	 *     path="/api/manage/event/{id}",
	 *     operationId="deleteEvent",
	 *     summary="Delete an event",
	 *     description="Delete an event by ID",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\Parameter(
	 *         name="id",
	 *         in="path",
	 *         required=true,
	 *         description="Event ID",
	 *         @OA\Schema(type="integer", example=1),
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Event deleted successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Event deleted successfully"),
	 *         ),
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="Event not found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Event not found"),
	 *         ),
	 *     ),
	 * )
	 */
	public function deleteEvent($id)
	{
		// Validate that $id is a valid integer
		if (!is_numeric($id) || (int)$id != $id) {
			return ApiResponse::error('Invalid event ID', 400);
		}

		// Delete Event Using ID
		$result = $this->eventManagementService->deleteEvent($id);

		// Return error if event not found & deleted
        if (!$result) {
            return ApiResponse::error('Event not found', 404);
        }

        return ApiResponse::success([], 'Event deleted successfully');
	}
}
