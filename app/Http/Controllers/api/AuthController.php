<?php

namespace App\Http\Controllers\api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
	/**
     * @OA\Post(
     *     tags={"Auth - Authentication"},
     *     path="/api/register",
     *     operationId="register",
     *     summary="User registration API",
     *     description="Register a new user",
	 *     @OA\RequestBody(
	 *         required=true,
	 *         description="User data to be registered",
	 *         @OA\MediaType(
	 *             mediaType="application/json",
	 *             @OA\Schema(
	 *                 type="object",
	 *                 required={"name", "email", "password"},
	 *                 @OA\Property(property="name", type="string", example="John Doe"),
	 *                 @OA\Property(property="email", type="string", example="john.doe@gmail.com"),
	 *                 @OA\Property(property="password", type="string", example="password1234"),
	 *             ),
	 *         ),
	 *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created Successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john.doe@gmail.com"),
     *                 @OA\Property(property="token", type="string", example="your_generated_token_here"),
     *             ),
     *             @OA\Property(property="message", type="array",
     *                 @OA\Items(type="string", default="User Successfully created"),
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation error"),
     *             @OA\Property(property="errors", type="object"),
     *         ),
     *     ),
     * )
     */
    public function register(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        // If validation fails, return a response with the validation errors
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);  // 422 Unprocessable Entity
        }

        // Create a new user in the database
        $user = User::create([
            'name' => $request->name,
			'role_id' => 1,
            'email' => $request->email,
            'password' => Hash::make($request->password),  // Make sure to hash the password
        ]);

        // Generate an API token for the new user
        $token = $user->createToken('Auth Token')->accessToken;

        // Return success response with the user data and token
        return response()->json([
            'status' => true,
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'token' => $token,
            ],
            'message' => ['User Successfully created'],
        ], 201);  // 201 Created
    }

	/**
	 * @OA\Post(
	 *     tags={"Auth - Authentication"},
	 *     path="/api/attendee-register",
	 *     operationId="attendeeRegister",
	 *     summary="Attendee registration API",
	 *     description="Register a new attendee",
	 *     @OA\RequestBody(
	 *         required=true,
	 *         description="Attendee data to be registered",
	 *         @OA\MediaType(
	 *             mediaType="application/json",
	 *             @OA\Schema(
	 *                 type="object",
	 *                 required={"name", "email", "password"},
	 *                 @OA\Property(property="name", type="string", example="John Doe"),
	 *                 @OA\Property(property="email", type="string", example="john.doe@gmail.com"),
	 *                 @OA\Property(property="password", type="string", example="password1234"),
	 *                 @OA\Property(property="address", type="string", example="123 Main St"),
	 *                 @OA\Property(property="phone", type="string", example="1234567890"),
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=201,
	 *         description="Registration Successful",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=true),
	 *             @OA\Property(property="data", type="object",
	 *                 @OA\Property(property="name", type="string", example="John Doe"),
	 *                 @OA\Property(property="email", type="string", example="john.doe@gmail.com"),
	 *                 @OA\Property(property="token", type="string", example="your_generated_token_here"),
	 *             ),
	 *             @OA\Property(property="message", type="array",
	 *                 @OA\Items(type="string", default="Registration Successful"),
	 *             ),
	 *         ),
	 *     ),
	 *     @OA\Response(
	 *         response=400,
	 *         description="Validation error",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Validation error"),
	 *             @OA\Property(property="errors", type="object"),
	 *         ),
	 *     ),
	 * )
	 */
	public function attendeeRegister(Request $request)
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
			'password' => Hash::make($request->password),  // Make sure to hash the password
			'address' => $request->address,
			'phone' => $request->phone,
		]);

		// Generate an API token for the new user
		$token = $user->createToken('Auth Token')->accessToken;

		// Return success response with the user data and token, but without phone and address
		return response()->json([
			'status' => true,
			'data' => [
				'name' => $user->name,
				'email' => $user->email,
				'token' => $token,
			],
			'message' => ['Registration Successful'],
		], 201);  // 201 Created
	}

    /**
	 *
	 * @OA\Post(
	 *     tags={"Auth - Authentication"},
	 *     path="/api/login",
	 *     operationId="login",
	 *     summary="User login API",
	 *     description="User login",
	 *     @OA\Header(
	 *         header="Content-Type",
	 *         @OA\Schema(
	 *             type="object",
	 *              @OA\Property(property="Content-Type", type="string", default="application/json"),
	 *              @OA\Property(property="Accept", type="string", default="application/json"),
	 *         ),
	 *     ),
	 *     @OA\RequestBody(
	 *         @OA\MediaType(
	 *             mediaType="application/json",
	 *             @OA\Schema(
	 *                 type="object",
	 *                 required={"email", "password"},
	 *                 @OA\Property(property="email", type="string", example="event@manager.com"),
	 *                 @OA\Property(property="password", type="string", example="Password@123"),
	 *             ),
	 *         ),
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="User Authorized",
	 *          @OA\JsonContent(
	 *              @OA\Property(property="status", type="boolean", example="true"),
	 *              @OA\Property(property="message", type="string", example="User Authorized"),
	 *              @OA\Property(property="data", type="object",
	 *                  @OA\Property(property="email", type="string", example="-----@gmail.com"),
	 *                  @OA\Property(property="password", type="string", example="abc1234"),
	 *              )
	 *          ),
	 *     ),
	 *     @OA\Response(
	 *          response=400,
	 *          description="Authentication error",
	 *          @OA\JsonContent(
	 *              @OA\Property(property="message", type="string", example="You have provided wrong credentials (or) your account does not exits!"),
	 *              @OA\Property(property="errors", type="object"),
	 *          ),
	 *     ),
	 * )
	 */
	public function login(Request $request)
    {
        // Validate the email and password
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        // If validation fails, return a response with the errors
		 // 422 Unprocessable Entity for validation errors
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Attempt to log in the user
        $credentials = $request->only('email', 'password');
        
        if (Auth::attempt($credentials)) {
			// Fetch the user from the database using the provided email
			$user = User::where('email', $request->email)->first();

			// Generate an API token for the fetched user
			return response()->json(array(
				'status' => true,
				'message' => 'User Authorized',
				'data' => array(
					'token'     => $user->createToken('Auth Token')->accessToken
				)
			));	
        }

		// Unauthorized if credentials are invalid
        return response()->json([
            'message' => 'Unauthorized'
        ], 401);
    }
}
