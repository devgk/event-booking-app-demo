<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Event;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EventManagementController extends Controller
{
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
			'location_country' => 'nullable|string|max:255',
			'event_state' => 'nullable|in:upcoming,past',
		]);

		if ($validator->fails()) {
			return response()->json([
				'status' => false,
				'message' => 'Validation error',
				'errors' => $validator->errors()
			], 422);
		}

		$pageNumber = $request->page_number;
		$location_country = $request->location_country;
		// Default to 'upcoming' if not provided
		$eventState = $request->event_state ?? 'upcoming';

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

		// Fetch the paginated events, passing the page number to paginate
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

		// Return paginated response with additional pagination info
		return response()->json([
			'status' => true,
			'message' => 'Event list fetched successfully',
			'data' => [
				'events' => $event_list,
				'current_page' => $events->currentPage(),
				'total_pages' => $events->lastPage(),
			],
			'message' => 'Event details fetched successfully',
		], 200);
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
			return response()->json([
				'message' => 'Invalid event ID',
			], 400);
		}

		// Find Event
		$event = Event::find($id);

		if (!$event) {
			return response()->json([
				'message' => 'Event not found',
			], 404);
		}

		// Get Total Bookings
		$total_bookings = $event->bookings()->count();

		return response()->json([
			'status' => true,
			'message' => 'Event details fetched successfully',
			'data' => [
				'event_name' => $event->name,
				'event_description' => $event->description,
				'event_date' => $event->event_date,
				'event_location_country' => $event->location_country,
				'seats_available' => $event->seats_available,
				'seats_booked' => $total_bookings,
			],
		], 200);
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
	public function createEvent(Request $request)
	{
		// Request Validation
		$validator = Validator::make($request->all(), [
			'name' => 'required|string|max:255',
			'description' => 'nullable|string',
			'event_date' => 'required|date|unique:events,event_date,NULL,id,name,' . $request->name,
			'seats_available' => 'required|integer|min:1',
			'location_country' => 'required|string|max:255',
		]);

		// Validation Error Response
		if ($validator->fails()) {
			return response()->json([
				'message' => 'Validation error',
				'errors' => $validator->errors()
			], 422);
		}

		// Get the current user's ID (authenticated via API token)
		$userId = Auth::guard('api')->user()->id;

		// Create Event
		$event = Event::create([
			'name' => $request->name,
			'description' => $request->description,
			'event_date' => $request->event_date,
			'seats_available' => $request->seats_available,
			'location_country' => strtoupper($request->location_country),
			'user_id' =>  $userId,
		]);

		// Return Response
		return response()->json([
			'status' => true,
			'message' => "Event created successfully.",
			'data' => [
				"id" => $event->id,
				"name" => $event->name,
				"description" => $event->description,
				"event_date" => $event->event_date,
				"seats_available" => $event->seats_available,
				"location_country" => $event->location_country,
			]
		], 201);
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
	public function updateEvent($id, Request $request)
	{
		// Validate that $id is a valid integer
		if (!is_numeric($id) || (int)$id != $id) {
			return response()->json([
				'message' => 'Invalid event ID',
			], 400);
		}

		// Find Event
		$event = Event::find($id);

		if (!$event) {
			return response()->json([
				'message' => 'Event not found',
			], 404);
		}

		// Request Validation
		$validator = Validator::make($request->all(), [
			'name' => 'required|string|max:255',
			'event_date' => 'required|date|unique:events,event_date,' . $id . ',id,name,' . $request->name,
			'location' => 'required|string|max:255',
			'description' => 'nullable|string',
		]);

		// Validation Error Response
		if ($validator->fails()) {
			return response()->json([
				'message' => 'Validation error',
				'errors' => $validator->errors()
			], 422);
		}

		// Update the event with the validated data
		$event->update([
			'name' => $request->name,
			'description' => $request->description,
			'event_date' => $request->event_date,
			'location' => $request->location,
			'seats_available' => $request->seats_available, // Update the seats_available field
		]);

		// Return Response with updated event data
		return response()->json([
			'status' => true,
			'message' => "Event updated successfully.",
			'data' => [
				"id" => $event->id,
				"name" => $event->name,
				"description" => $event->description,
				"event_date" => $event->event_date,
				"location_country" => $event->location_country,
				"seats_available" => $event->seats_available,
			]
		], 200);
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
			return response()->json([
				'message' => 'Invalid event ID',
			], 400);
		}

		// Find Event
		$event = Event::find($id);

		if (!$event) {
			return response()->json([
				'message' => 'Event not found',
			], 404);
		}

		// Delete Event
		$event->delete();

		return response()->json([
			'status' => true,
			'message' => 'Event deleted successfully',
		], 200);
	}
}
