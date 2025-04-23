<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
        /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
		'event_id',
		'user_id',
		'booking_date',
    ];

	// Define the relationship to the Event model
	public function event()
	{
		return $this->belongsTo(Event::class); // Each booking belongs to an event
	}

	// Define the relationship to the User model
	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
