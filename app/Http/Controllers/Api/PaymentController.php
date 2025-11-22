<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;

class PaymentController extends Controller
{
    /**
     * Get Paystack public key
     */
    public function getPublicKey()
    {
        $settings = DB::table('payment_gateway_settings')->first();

        if (!$settings || !$settings->paystack_public_key_encrypted) {
            return response()->json([
                'message' => 'Payment settings not configured'
            ], 500);
        }

        try {
            $publicKey = Crypt::decryptString($settings->paystack_public_key_encrypted);

            return response()->json([
                'public_key' => $publicKey,
                'is_live_mode' => $settings->is_live_mode,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving payment settings'
            ], 500);
        }
    }

    /**
     * Initialize payment transaction
     */
    public function initialize(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
        ]);

        $user = $request->user();
        $course = Course::findOrFail($validated['course_id']);

        // Check if already enrolled
        if ($user->isEnrolledIn($course->id)) {
            return response()->json([
                'message' => 'You are already enrolled in this course',
            ], 400);
        }

        // Get Paystack secret key
        $settings = DB::table('payment_gateway_settings')->first();

        if (!$settings || !$settings->paystack_secret_key_encrypted) {
            return response()->json([
                'message' => 'Payment gateway not configured'
            ], 500);
        }

        try {
            $secretKey = Crypt::decryptString($settings->paystack_secret_key_encrypted);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving payment settings'
            ], 500);
        }

        // Initialize Paystack transaction
        $reference = 'GGTL-' . strtoupper(uniqid());
        $amount = $course->price * 100; // Convert to kobo (cents)

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $secretKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.paystack.co/transaction/initialize', [
            'email' => $user->email,
            'amount' => $amount,
            'reference' => $reference,
            'currency' => 'NGN',
            'metadata' => [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'course_title' => $course->title,
                'custom_fields' => [
                    [
                        'display_name' => 'Customer Name',
                        'variable_name' => 'customer_name',
                        'value' => $user->full_name,
                    ]
                ],
            ],
            'callback_url' => env('FRONTEND_URL') . '/payment/success?reference=' . $reference,
        ]);

        if (!$response->successful()) {
            return response()->json([
                'message' => 'Failed to initialize payment',
                'error' => $response->json(),
            ], 500);
        }

        $data = $response->json('data');

        return response()->json([
            'authorization_url' => $data['authorization_url'],
            'access_code' => $data['access_code'],
            'reference' => $data['reference'],
        ]);
    }

    /**
     * Verify payment transaction
     */
    public function verify(Request $request)
    {
        $validated = $request->validate([
            'reference' => 'required|string',
        ]);

        // Get Paystack secret key
        $settings = DB::table('payment_gateway_settings')->first();

        if (!$settings || !$settings->paystack_secret_key_encrypted) {
            return response()->json([
                'message' => 'Payment gateway not configured'
            ], 500);
        }

        try {
            $secretKey = Crypt::decryptString($settings->paystack_secret_key_encrypted);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving payment settings'
            ], 500);
        }

        // Verify transaction with Paystack
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $secretKey,
        ])->get('https://api.paystack.co/transaction/verify/' . $validated['reference']);

        if (!$response->successful()) {
            return response()->json([
                'message' => 'Failed to verify payment',
            ], 400);
        }

        $data = $response->json('data');

        if ($data['status'] !== 'success') {
            return response()->json([
                'message' => 'Payment was not successful',
                'status' => $data['status'],
            ], 400);
        }

        // Get metadata
        $metadata = $data['metadata'];
        $userId = $metadata['user_id'];
        $courseId = $metadata['course_id'];

        // Create enrollment
        $enrollment = Enrollment::firstOrCreate(
            [
                'user_id' => $userId,
                'course_id' => $courseId,
            ],
            [
                'enrolled_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Payment verified successfully',
            'enrollment' => $enrollment->load('course'),
        ]);
    }

    /**
     * Paystack webhook handler
     */
    public function webhook(Request $request)
    {
        // Verify webhook signature
        $signature = $request->header('x-paystack-signature');
        $body = $request->getContent();

        $settings = DB::table('payment_gateway_settings')->first();
        $secretKey = Crypt::decryptString($settings->paystack_secret_key_encrypted);

        $computedSignature = hash_hmac('sha512', $body, $secretKey);

        if ($signature !== $computedSignature) {
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $event = $request->input('event');
        $data = $request->input('data');

        if ($event === 'charge.success') {
            $metadata = $data['metadata'];
            $userId = $metadata['user_id'];
            $courseId = $metadata['course_id'];

            // Create enrollment
            Enrollment::firstOrCreate(
                [
                    'user_id' => $userId,
                    'course_id' => $courseId,
                ],
                [
                    'enrolled_at' => now(),
                ]
            );
        }

        return response()->json(['message' => 'Webhook processed']);
    }

    /**
     * Admin: Update payment settings
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'paystack_public_key' => 'required|string',
            'paystack_secret_key' => 'required|string',
            'is_live_mode' => 'required|boolean',
        ]);

        try {
            $publicKeyEncrypted = Crypt::encryptString($validated['paystack_public_key']);
            $secretKeyEncrypted = Crypt::encryptString($validated['paystack_secret_key']);

            DB::table('payment_gateway_settings')->updateOrInsert(
                ['id' => 1],
                [
                    'paystack_public_key_encrypted' => $publicKeyEncrypted,
                    'paystack_secret_key_encrypted' => $secretKeyEncrypted,
                    'is_live_mode' => $validated['is_live_mode'],
                    'updated_at' => now(),
                ]
            );

            return response()->json([
                'message' => 'Payment settings updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating payment settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Get payment settings (masked)
     */
    public function getSettings()
    {
        $settings = DB::table('payment_gateway_settings')->first();

        if (!$settings) {
            return response()->json([
                'is_configured' => false,
                'is_live_mode' => false,
            ]);
        }

        return response()->json([
            'is_configured' => !empty($settings->paystack_public_key_encrypted),
            'is_live_mode' => $settings->is_live_mode,
            'public_key_preview' => !empty($settings->paystack_public_key_encrypted) ? 'pk_****' : null,
        ]);
    }
}
