<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Event;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AttendeeManagementController extends Controller
{
	/**
	 * @OA\Post(
	 *     tags={"Attendee Management"},
	 *     path="/api/users/attendee/all",
	 *     operationId="viewAttendee",
	 *     summary="List event attendees with pagination",
	 *     description="Fetches a paginated list of users with role (attendees). Accepts page_number in the request body.",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\RequestBody(
	 *         required=false,
	 *         description="Page number to fetch",
	 *         @OA\MediaType(
	 *             mediaType="application/json",
	 *             @OA\Schema(
	 *                 type="object",
	 *                 @OA\Property(property="page_number", type="integer", example=1)
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="List of attendees fetched successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Attendees fetched successfully"),
	 *             @OA\Property(property="data", type="object",
	 *                 @OA\Property(property="current_page", type="integer", example=1),
	 *                 @OA\Property(property="total_pages", type="integer", example=5),
	 *                 @OA\Property(
	 *                     property="attendees",
	 *                     type="array",
	 *                     @OA\Items(
	 *                         type="object",
	 *                         @OA\Property(property="id", type="integer", example=1),
	 *                         @OA\Property(property="name", type="string", example="John Doe"),
	 *                         @OA\Property(property="email", type="string", example="john@example.com"),
	 *                         @OA\Property(property="total_events_booked", type="integer", example=3)
	 *                     )
	 *                 )
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=400,
	 *         description="Invalid page number",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Bad Request")
	 *         )
	 *     )
	 * )
	 */
	public function viewAllAttendeeUsers(Request $request)
	{
		$pageNumber = $request->page_number;
		$perPage = 10;

		if (!is_numeric($pageNumber) || $pageNumber < 1) {
			return response()->json([
				'status' => false,
				'message' => 'Invalid page number. Must be a positive integer.'
			], 400);
		}

		// Query the users with role_id = 2 (attendees)
		$query = User::where('role_id', 2);

		$totalRecords = $query->count();
		$totalPages = max(ceil($totalRecords / $perPage), 1);

		// Validate if page number exceeds total pages
		if ($pageNumber > $totalPages) {
			return response()->json([
				'status' => false,
				'message' => "Page number exceeds total pages. Max page number is {$totalPages}."
			], 400);
		}

		// Fetch attendees with total events booked using eager loading and counting the bookings
		$attendees = $query
			->select('id', 'name', 'email')
			->skip(($pageNumber - 1) * $perPage)
			->take($perPage)
			->withCount('bookings')
			->get();

		return response()->json([
			'status' => true,
			'message' => 'Attendees fetched successfully',
			'data' => [
				'current_page' => (int) $pageNumber,
				'total_pages' => (int) $totalPages,
				'attendees' => $attendees->map(function ($attendee) {
					$attendee->total_events_booked = $attendee->bookings_count;
					return $attendee;
				}),
			]
		], 200);
	}

	/**
	 * @OA\Post(
	 *     tags={"Attendee Management"},
	 *     path="/api/users/attendee",
	 *     operationId="addAttendeeUser",
	 *     summary="Add a new attendee user",
	 *     description="Adds a new user with the role of an attendee.",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\RequestBody(
	 *         required=true,
	 *         description="User data to be added as an attendee",
	 *         @OA\MediaType(
	 *             mediaType="application/json",
	 *             @OA\Schema(
	 *                 type="object",
	 *                 required={"name", "email", "password"},
	 *                 @OA\Property(property="name", type="string", example="John Doe"),
	 *                 @OA\Property(property="email", type="string", example="johndoe@gmail.com"),
	 *                 @OA\Property(property="password", type="string", example="password123"),
	 *                 @OA\Property(property="address", type="string", example="123 Main St"),
	 *                 @OA\Property(property="phone", type="string", example="1234567890")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=201,
	 *         description="Attendee added successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=true),
	 *             @OA\Property(property="message", type="array",
	 *                 @OA\Items(type="string", example="Attendee Added Successfully.")
	 *             ),
	 *             @OA\Property(property="data", type="object",
	 *                 @OA\Property(property="name", type="string", example="John Doe"),
	 *                 @OA\Property(property="email", type="string", example="johndoe@gmail.com"),
	 *                 @OA\Property(property="address", type="string", example="123 Main St"),
	 *                 @OA\Property(property="phone", type="string", example="1234567890")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=422,
	 *         description="Validation error",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Validation error"),
	 *             @OA\Property(property="errors", type="object",
	 *                 @OA\Property(property="name", type="array", @OA\Items(type="string", example="The name field is required.")),
	 *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="The email has already been taken."))
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=400,
	 *         description="Bad Request",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Bad Request")
	 *         )
	 *     )
	 * )
	 */
	public function addAttendeeUser(Request $request)
	{
		// Validate the request data
		$validator = Validator::make($request->all(), [
			'name' => 'required|string|max:255',
			'email' => 'required|email|unique:users,email',
			'password' => 'required|string|min:6',
			'address' => 'nullable|string|max:255',
			'phone' => 'nullable|string|max:10',
		]);

		// If validation fails, return a response with the validation errors
		if ($validator->fails()) {
			return response()->json([
				'message' => 'Validation error',
				'errors' => $validator->errors()
			], 422);  // 422 Unprocessable Entity
		}

		// Create a new attendee in the database
		$user = User::create([
			'name' => $request->name,
			'role_id' => 2,
			'email' => $request->email,
			'password' => Hash::make($request->password),
			'address' => $request->address,
			'phone' => $request->phone,
		]);

		// Return success response with the user data
		return response()->json([
			'status' => true,
			'message' => ['Attendee Added Successfully.'],
			'data' => [
				'name' => $user->name,
				'email' => $user->email,
				'address' => $user->address,
				'phone' => $user->phone
			],
		], 201);  // 201 Created
	}

	/**
	 * @OA\Put(
	 *     tags={"Attendee Management"},
	 *     path="/api/users/attendee/{id}",
	 *     operationId="updateAttendeeUser",
	 *     summary="Update an existing attendee user",
	 *     description="Updates an existing attendee user's account by passing raw JSON and the user ID in the request body.",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\Parameter(
	 *         name="id",
	 *         in="path",
	 *         required=true,
	 *         description="User ID of the attendee to update",
	 *         @OA\Schema(type="integer", example=1),
	 *     ),
	 *     @OA\RequestBody(
	 *         required=true,
	 *         description="User data to be updated",
	 *         @OA\MediaType(
	 *             mediaType="application/json",
	 *             @OA\Schema(
	 *                 type="object",
	 *                 required={"name", "email"},
	 *                 @OA\Property(property="name", type="string", example="John Doe"),
	 *                 @OA\Property(property="email", type="string", example="john.doe@gmail.com"),
	 *                 @OA\Property(property="password", type="string", example="newpassword123"),
	 *                 @OA\Property(property="address", type="string", example="456 New Street"),
	 *                 @OA\Property(property="phone", type="string", example="9876543210")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Attendee user updated successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Attendee user updated successfully."),
	 *             @OA\Property(property="data", type="object",
	 *                 @OA\Property(property="name", type="string", example="John Doe Updated"),
	 *                 @OA\Property(property="email", type="string", example="john.doe.updated@gmail.com"),
	 *                 @OA\Property(property="address", type="string", example="456 New Street"),
	 *                 @OA\Property(property="phone", type="string", example="9876543210")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="User not found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="User not found")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=422,
	 *         description="Validation error",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Validation error"),
	 *             @OA\Property(property="errors", type="object",
	 *                 @OA\Property(property="name", type="array", @OA\Items(type="string", example="The name field is required.")),
	 *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="The email has already been taken."))
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=400,
	 *         description="Bad Request",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Bad Request")
	 *         )
	 *     )
	 * )
	 */
	public function updateAttendeeUser($id, Request $request)
	{
		// Validate that $id is a valid integer
		if (!is_numeric($id) || (int)$id != $id) {
			return response()->json([
				'message' => 'Invalid event ID',
			], 400);
		}

		// Find the user by ID
		$user = User::find($id);

		// If the user does not exist, return an error response
		if (!$user) {
			return response()->json([
				'message' => 'User not found',
			], 404);
		}

		// Validate the request data
		$validator = Validator::make($request->all(), [
			'name' => 'required|string|max:255',
			'email' => 'required|email|unique:users,email,' . $id,
			'password' => 'nullable|string|min:6',
			'address' => 'nullable|string|max:255',
			'phone' => 'nullable|string|max:10',
		]);

		// If validation fails, return a response with the validation errors
		if ($validator->fails()) {
			return response()->json([
				'message' => 'Validation error',
				'errors' => $validator->errors()
			], 422);
		}

		// Update the user with the validated data
		$user->update([
			'name' => $request->name,
			'email' => $request->email,
			'password' => $request->password ? Hash::make($request->password) : $user->password,
			'address' => $request->address,
			'phone' => $request->phone,
		]);

		// Return success response with the updated user data
		return response()->json([
			'status' => true,
			'message' => 'Attendee user updated successfully.',
			'data' => [
				'name' => $user->name,
				'email' => $user->email,
				'address' => $user->address,
				'phone' => $user->phone,
			]
		], 200);
	}

	/**
	 * @OA\Post(
	 *     tags={"Attendee Management"},
	 *     path="/api/manage/events/{id}/view-attendees",
	 *     operationId="viewAttendeeOfEvent",
	 *     summary="List attendees of a specific event with pagination",
	 *     description="Fetches a paginated list of attendees for a specific event by event ID. The page number is passed in the request body.",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\Parameter(
	 *         name="id",
	 *         in="path",
	 *         required=true,
	 *         description="Event ID",
	 *         @OA\Schema(type="integer", example=1),
	 *     ),
	 *     @OA\RequestBody(
	 *         required=false,
	 *         description="Page number to fetch",
	 *         @OA\MediaType(
	 *             mediaType="application/json",
	 *             @OA\Schema(
	 *                 type="object",
	 *                 @OA\Property(property="page_number", type="integer", example=1)
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="List of attendees fetched successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Attendees fetched successfully"),
	 *             @OA\Property(property="data", type="object",
	 *                 @OA\Property(
	 *                     property="attendees",
	 *                     type="array",
	 *                     @OA\Items(
	 *                         type="object",
	 *                         @OA\Property(property="id", type="integer", example=1),
	 *                         @OA\Property(property="name", type="string", example="John Doe"),
	 *                         @OA\Property(property="email", type="string", example="john@example.com")
	 *                     )
	 *                 ),
	 *                 @OA\Property(property="current_page", type="integer", example=1),
	 *                 @OA\Property(property="total_pages", type="integer", example=5)
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=400,
	 *         description="Invalid page number",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Bad Request")
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
	public function viewAttendeeOfEvent($id, Request $request)
	{
		// Validate that $id is a valid integer
		if (!is_numeric($id) || (int)$id != $id) {
			return response()->json([
				'message' => 'Invalid event ID',
			], 400);
		}

		// Fetch the event based on the provided event ID
		$event = Event::find($id);

		if (!$event) {
			return response()->json([
				'status' => false,
				'message' => 'Event not found',
			], 404);
		}

		// Validate the provided page number
		$validator = Validator::make($request->all(), [
			'page_number' => 'nullable|integer|min:1',
		]);

		if ($validator->fails()) {
			return response()->json([
				'status' => false,
				'message' => 'Validation error',
				'errors' => $validator->errors()
			], 422);
		}

		// Set the page number and per page
		$pageNumber = $request->page_number ?: 1;
		$perPage = 10;

		// Get the paginated attendees for this event (role_id = 2)
		// Eager load user data
		// Only get attendees (role_id = 2)
		$attendees = Booking::where('event_id', $id)
			->whereHas('user', function ($query) {
				$query->where('role_id', 2);
			})
			->with('user') 
			->paginate($perPage, ['*'], 'page', $pageNumber);

		// If no attendees are found, return a message
		if ($attendees->isEmpty()) {
			return response()->json([
				'status' => false,
				'message' => "No attendees found for event with ID: {$id}.",
			], 404);
		}

		// Return the list of attendees with pagination details inside the data field
		$attendeesData = $attendees->map(function ($booking) {
			return [
				'id' => $booking->user->id,
				'name' => $booking->user->name,
				'email' => $booking->user->email
			];
		});

		return response()->json([
			'status' => true,
			'message' => 'Attendees fetched successfully',
			'data' => [
				'current_page' => $attendees->currentPage(),
				'total_pages' => $attendees->lastPage(),
				'attendees' => $attendeesData,
			]
		], 200);
	}

	/**
	 * @OA\Post(
	 *     tags={"Attendee Management"},
	 *     path="/api/manage/events/{id}/add-attendee",
	 *     operationId="addAttendeeToEvent",
	 *     summary="Add an attendee to a specific event",
	 *     description="Adds an attendee to a specific event by providing their email. The attendee will be added if they are not already registered for the event.",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\Parameter(
	 *         name="id",
	 *         in="path",
	 *         required=true,
	 *         description="The ID of the event to which the attendee is being added",
	 *         @OA\Schema(type="integer", example=1),
	 *     ),
	 *     @OA\RequestBody(
	 *         required=true,
	 *         description="User data (email) to be added to the event as an attendee",
	 *         @OA\MediaType(
	 *             mediaType="application/json",
	 *             @OA\Schema(
	 *                 type="object",
	 *                 required={"email"},
	 *                 @OA\Property(property="email", type="string", example="johndoe@example.com"),
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=201,
	 *         description="Attendee added successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Attendee added to the event successfully"),
	 *             @OA\Property(property="data", type="object",
	 *                 @OA\Property(property="user_id", type="integer", example=1),
	 *                 @OA\Property(property="user_name", type="string", example="John Doe"),
	 *                 @OA\Property(property="event_id", type="integer", example=2),
	 *                 @OA\Property(property="event_name", type="string", example="Sample Event"),
	 *                 @OA\Property(property="booking_date", type="string", example="2025-04-23 14:32:15")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=400,
	 *         description="Bad Request (Invalid input, user already added, etc.)",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="User is already an attendee of this event")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="Event not found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Event not found")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=422,
	 *         description="Validation error",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Validation error"),
	 *             @OA\Property(property="errors", type="object",
	 *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="The email field is required."))
	 *             )
	 *         )
	 *     )
	 * )
	 */
	public function addAttendeeToEvent($id, Request $request)
	{
		// Validate that $id is a valid integer
		if (!is_numeric($id) || (int)$id != $id) {
			return response()->json([
				'status' => false,
				'message' => 'Invalid event ID',
			], 400);
		}
	
		// Fetch the event by ID
		$event = Event::find($id);
	
		if (!$event) {
			return response()->json([
				'status' => false,
				'message' => 'Event not found',
			], 404);
		}

		 // Check if there are available seats
		 $totalBookings = Booking::where('event_id', $id)->count();
		 if ($totalBookings >= $event->seats_available) {
			 return response()->json([
				 'status' => false,
				 'message' => 'No available seats for this event.',
			 ], 400);
		 }
	
		// Validate the request data (email is required)
		$validator = Validator::make($request->all(), [
			'email' => 'required|email|exists:users,email', // Check if the user exists
		]);
	
		// If validation fails, return a response with the validation errors
		if ($validator->fails()) {
			return response()->json([
				'status' => false,
				'message' => 'Validation error',
				'errors' => $validator->errors()
			], 422);
		}
	
		// Fetch the user by email
		$user = User::where('email', $request->email)->first();
	
		// Check if the user is already an attendee of this event
		$existingBooking = Booking::where('event_id', $id)
			->where('user_id', $user->id)
			->first();
	
		if ($existingBooking) {
			return response()->json([
				'status' => false,
				'message' => 'User is already an attendee of this event',
			], 400);
		}
	
		// Create a new booking for the user and the event
		$booking = Booking::create([
			'event_id' => $id,
			'user_id' => $user->id,
			'booking_date' => Carbon::now(),
		]);
	
		return response()->json([
			'status' => true,
			'message' => 'Attendee added to the event successfully',
			'data' => [
				'user_id' => $user->id,
				'user_name' => $user->name,
				'event_id' => $event->id,
				'event_name' => $event->name,
				'booking_date' => $booking->booking_date,
			]
		], 201); // 201 Created
	}
	
	/**
	 * @OA\Post(
	 *     tags={"Attendee Management"},
	 *     path="/api/manage/events/{id}/remove-attendee",
	 *     operationId="removeAttendeeFromEvent",
	 *     summary="Remove an attendee from a specific event",
	 *     description="Removes an attendee from a specific event using the provided email. The user must be a registered attendee of the event.",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\Parameter(
	 *         name="id",
	 *         in="path",
	 *         required=true,
	 *         description="The ID of the event from which the attendee is being removed",
	 *         @OA\Schema(type="integer", example=1),
	 *     ),
	 *     @OA\RequestBody(
	 *         required=true,
	 *         description="User data (email) to be removed from the event",
	 *         @OA\MediaType(
	 *             mediaType="application/json",
	 *             @OA\Schema(
	 *                 type="object",
	 *                 required={"email"},
	 *                 @OA\Property(property="email", type="string", example="johndoe@example.com"),
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Attendee removed successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Attendee removed from the event successfully"),
	 *             @OA\Property(property="data", type="object",
	 *                 @OA\Property(property="user_id", type="integer", example=1),
	 *                 @OA\Property(property="user_name", type="string", example="John Doe"),
	 *                 @OA\Property(property="event_id", type="integer", example=2),
	 *                 @OA\Property(property="event_name", type="string", example="Sample Event")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="Event not found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Event not found")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=400,
	 *         description="User is not an attendee of this event",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="User is not an attendee of this event")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=422,
	 *         description="Validation error",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Validation error"),
	 *             @OA\Property(property="errors", type="object",
	 *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="The email field is required."))
	 *             )
	 *         )
	 *     )
	 * )
	 */
	public function removeAttendeeFromEvent($id, Request $request)
	{
		// Validate that $id is a valid integer
		if (!is_numeric($id) || (int)$id != $id) {
			return response()->json([
				'status' => false,
				'message' => 'Invalid event ID',
			], 400);
		}
	
		// Fetch the event by ID
		$event = Event::find($id);
	
		if (!$event) {
			return response()->json([
				'status' => false,
				'message' => 'Event not found',
			], 404);
		}

		// Validate the request data
		$validator = Validator::make($request->all(), [
			'email' => 'required|email|exists:users,email', // Ensure the user exists in the system
		]);
	
		// If validation fails, return a response with the validation errors
		if ($validator->fails()) {
			return response()->json([
				'status' => false,
				'message' => 'Validation error',
				'errors' => $validator->errors()
			], 422);
		}
	
		// Fetch the user by email
		$user = User::where('email', $request->email)->first();
	
		// Check if the user is an attendee for this event
		$booking = Booking::where('event_id', $id)
			->where('user_id', $user->id)
			->first();
	
		if (!$booking) {
			return response()->json([
				'status' => false,
				'message' => 'User is not an attendee of this event',
			], 400);
		}
	
		// Remove the booking (i.e., remove the user from the event)
		$booking->delete();
	
		return response()->json([
			'status' => true,
			'message' => 'Attendee removed from the event successfully',
			'data' => [
				'user_id' => $user->id,
				'user_name' => $user->name,
				'event_id' => $event->id,
				'event_name' => $event->name,
			]
		], 200);
	}
}
