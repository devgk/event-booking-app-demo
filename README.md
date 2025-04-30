# Event Booking System
This is a Laravel-based event booking system, allowing users to create events, register as attendees, book event, and manage events through an API interface.

### Server Requirements
- PHP >= 8.2
- Ctype PHP Extension
- cURL PHP Extension
- DOM PHP Extension
- Fileinfo PHP Extension
- Filter PHP Extension
- Hash PHP Extension
- Mbstring PHP Extension
- OpenSSL PHP Extension
- PCRE PHP Extension
- PDO PHP Extension
- Session PHP Extension
- Tokenizer PHP Extension
- XML PHP Extension
- Sodium PHP Extension

### Project Setup
1. Composer Install
2. Create a new database for the project and update the .env file with your database credentials. For example:
   ```
	DB_CONNECTION=mysql
	DB_HOST=127.0.0.1
	DB_PORT=3306
	DB_DATABASE=event_booking_db
	DB_USERNAME=root
	DB_PASSWORD=123456
   ```
3. Run migration and seed database
   ```
	php artisan migrate:fresh --seed
   ```
   Seeding will create one Event Manager and Few Events for listing.
4. Create personal client token for passport
   ```
	php artisan passport:client --personal
   ```
   It will ask for personal access client token name - provide one for example "Auth"
5. Run Laravel Server
   ```
	php artisan serve
   ```
6. Update app url in .env file
   ```
	APP_URL=http://127.0.0.1:8000
	L5_SWAGGER_CONST_HOST=http://127.0.0.1:8000
   ```
7. Open app url (http://127.0.0.1:8000) in your browser to access the APP

### APIs

1. Login - to get token (Attendee can also login. As roles and permission are set so Attendee cannot manage events or perform Event manager tasks)
2. Register - For Event Manager
3. Attendee Register - So that Attendee can register
4. api/events - Get a list of all events with pagination and event state filtering support.
5. api/event/{id} - Get the details of a specific event, including the event name, description, date, location, available seats, and total bookings.
6. api/manage/event - Create a new event by passing raw JSON and Bearer token in the header.
7. api/manage/event/{id} - PUT - Update event details
8. api/manage/event/{id} - DELETE - Delete an event by ID
9. Check other API details in swagger doc

### Event Admin Flow
1. Login with Event Admin - Preset in login API (Swagger)
2. Use token to Authenticate Swagger
3. Add Event
4. Check Event Listing
   1. Filter by location
   2. Filter by state (upcoming or past events)
5. Register Attendee
6. Update Attendee
7. Add Attendee to Event
   1. Event Seat Check - Attendee cannot be added more than the seats available in event
8. Update Event
9. Delete Event
10. List Attendees all
    1.  With number of booking done by each attendee
11. List Attendee of a particular event
12. Remove Attendee from event

### Attendee
1. List Events (api/events)
2. Book Event
   1. With authentication
   2. New user account will be created if does not exists
   3. Preventing Duplicate Booking


### Recent Changes
1. Created a Request Validation CLass  - To Validate Th request
2. Created a Service Classes - To move the core logic to services folder
3. Created a Custom rule Class - To Add custom rule for validation
