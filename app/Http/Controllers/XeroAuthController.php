<?php 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\XeroToken;

class XeroAuthController extends Controller
{
    private $client_id;
    private $client_secret;
    private $redirect_uri;
    private $identity_url;

    public function __construct()
    {
        $this->client_id = env('XERO_CLIENT_ID');
        $this->client_secret = env('XERO_CLIENT_SECRET');
        $this->redirect_uri = env('XERO_REDIRECT_URI');
        $this->identity_url = env('XERO_IDENTITY_URL'); // ✅ Xero Identity URL from .env
    }

    // Step 1: Redirect User to Xero Authorization Page
    public function redirectToXero()
    {  
        $clientId = "B0CAE331402B42C8833DC0E39D284BA4"; // Replace with your actual Client ID
        $redirectUri = "https://api-explorer.xero.com/";
        $scope = "openid profile email accounting.transactions accounting.contacts accounting.settings offline_access";
        $state = "123";
    
        $url = "https://login.xero.com/identity/connect/authorize?"
            . "response_type=code"
            . "&client_id={$clientId}"
            . "&redirect_uri={$redirectUri}"
            . "&scope=" . str_replace(" ", "%20", $scope) // Replace spaces with %20
            . "&state={$state}";
    
        return redirect()->away($url);
    }

    // Step 2: Handle Callback and Save Tokens in Database
    public function handleCallback(Request $request)
    {
        if ($request->has('code')) {
            $response = Http::asForm()->post("{$this->identity_url}/connect/token", [
                'grant_type' => 'authorization_code',
                'code' => $request->code,
                'redirect_uri' => $this->redirect_uri,
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
            ]);

            $data = $response->json();

            if (isset($data['access_token'])) {
                XeroToken::updateOrCreate([], [
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'],
                    'expires_at' => now()->addSeconds($data['expires_in'])
                ]);

                return response()->json(['message' => 'Xero authorized successfully', 'data' => $data]);
            }
        }

        return response()->json(['error' => 'Authorization failed'], 400);
    }

    // Step 3: Refresh Token Automatically
    // public function refreshToken()
    // {
    //     $token = XeroToken::first();

    //     if (!$token) {
    //         return response()->json(['error' => 'No token found. Please authorize first.'], 400);
    //     }

    //     $response = Http::asForm()->post("https://identity.xero.com/connect/token", [
    //         'grant_type' => 'refresh_token',
    //         'refresh_token' => $token->refresh_token,
    //         'client_id' => $this->client_id,
    //         'client_secret' => $this->client_secret,
    //     ]);

    //     $data = $response->json(); 

    //     $response = Http::withHeaders([
    //         'Authorization' => 'Bearer ' . $token->access_token,
    //         'Accept' => 'application/json',
    //         'Content-Type' => 'application/json',
    //     ])->get('https://api.xero.com/connections');
        
    //     $dataOftenant = $response->json(); 
    //     $dataOftenant['tenantId'];

    //     if (isset($data['access_token'])) {
    //         $token->update([
    //             'access_token' => $data['access_token'],
    //             'refresh_token' => $data['refresh_token'],
    //             'tenant_id' => $dataOftenant['tenantId'],
    //             'expires_at' => now()->addSeconds($data['expires_in'])
    //         ]);

    //         return response()->json(['message' => 'Token refreshed successfully', 'data' => $data]);
    //     }

    //     return response()->json(['error' => 'Token refresh failed'], 400);
    // }  

    public function refreshToken() 
{   
    $token = XeroToken::first();

    if (!$token) {
        return response()->json(['error' => 'No token found. Please authorize first.'], 400);
    }

    $response = Http::asForm()->post("https://identity.xero.com/connect/token", [
        'grant_type' => 'refresh_token',
        'refresh_token' => $token->refresh_token,
        'client_id' => $this->client_id,
        'client_secret' => $this->client_secret,
    ]);

    $data = $response->json(); 

    // Handle token refresh errors
    if (!$response->successful()) {
        return response()->json([
            'error' => 'Token refresh request failed',
            'details' => $data
        ], $response->status());
    }

    if (isset($data['error']) && $data['error'] === 'invalid_grant') {
        return response()->json([
            'error' => 'Invalid refresh token. Please reauthorize.'
        ], 400);
    }

    // Fetch tenant ID
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $data['access_token'],
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ])->get('https://api.xero.com/connections');

    $dataOftenant = $response->json();

    if (!$response->successful() || !isset($dataOftenant[0]['tenantId'])) {
        return response()->json([
            'error' => 'Failed to fetch tenant details',
            'details' => $dataOftenant
        ], $response->status());
    }

    $tenantId = $dataOftenant[0]['tenantId'];

    // Update token in database
    $token->update([
        'access_token' => $data['access_token'],
        'refresh_token' => $data['refresh_token'],
        'tenant_id' => $tenantId,
        'expires_at' => now()->addSeconds($data['expires_in'])
    ]);

    return response()->json(['message' => 'Token refreshed successfully', 'data' => $data]);
}


    public function getContacts()
    {
        $token = XeroToken::first();

        if (!$token) {
            return response()->json(['error' => 'No token found. Please authorize first.'], 400);
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token->access_token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'xero-tenant-id' => $token->tenant_id
        ])->get('https://api.xero.com/api.xro/2.0/Contacts');
        
        $data = $response->json();
        return response()->json($data);

        
    }
}
