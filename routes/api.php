<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\AttendeeController;
use App\Http\Controllers\api\EventManagementController;
use App\Http\Controllers\Api\AttendeeManagementController;

// Authentication & Authorization APIs
Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);
Route::post('attendee-register', [AuthController::class, 'attendeeRegister']);

// Public APIs
Route::post('events', [EventManagementController::class, 'listEvents']);
Route::get('event/{id}', [EventManagementController::class, 'viewEvent']);
Route::post('book-event', [AttendeeController::class, 'bookEvent']);
Route::post('get-my-booked-events', [AttendeeController::class, 'getMyBookedEvents']);

// Protected APIs
Route::middleware('event.admin')->group(function () {
	// Attendee Management
	Route::post('users/attendee/all', [AttendeeManagementController::class, 'viewAllAttendeeUsers']);
	Route::post('users/attendee/add', [AttendeeManagementController::class, 'addAttendeeUser']);
	Route::put('users/attendee/{id}', [AttendeeManagementController::class, 'updateAttendeeUser']);

	// Event Management
	Route::post('manage/event', [EventManagementController::class, 'createEvent']);
	Route::put('manage/event/{id}', [EventManagementController::class, 'updateEvent']);
	Route::delete('manage/event/{id}', [EventManagementController::class, 'deleteEvent']);

	// Attendee of Events Management
	Route::post('manage/events/{id}/view-attendees', [AttendeeManagementController::class, 'viewAttendeeOfEvent']);
	Route::post('manage/events/{id}/add-attendee', [AttendeeManagementController::class, 'addAttendeeToEvent']);
	Route::post('manage/events/{id}/remove-attendee', [AttendeeManagementController::class, 'removeAttendeeFromEvent']);
});
