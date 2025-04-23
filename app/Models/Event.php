<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Event extends Model
{
	use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'event_date',
		'seats_available',
		'location_country',
		'user_id',
    ];

	// Define the relationship to bookings table
	public function bookings()
	{
		return $this->hasMany(Booking::class);
	}
}
