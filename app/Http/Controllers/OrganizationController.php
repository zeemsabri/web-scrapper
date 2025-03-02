<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use App\Models\Organization;
use App\Models\Person;
use App\Models\Deal;
use Illuminate\Support\Facades\DB;

class OrganizationController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'projectName' => 'required',
            'organizationsArray' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            $deals = [];
            $existingDeal = Deal::where('job_id', $request->projectId)->count();
            $organizationsArrayCount = is_array($request->organizationsArray ?? null) ? count($request->organizationsArray) : 0;
            if ($organizationsArrayCount == $existingDeal) {
                return response()->json(['message' => '⚠️ This job is already posted!'], 409);
            }
            if(isset($request->organizationsArray )){
                foreach ($request->organizationsArray as $index => $row) {
                    if ($index >= $existingDeal) { 
                        $organizationData = $this->getOrCreateOrganization($row['builderName']);
                        $personData = $this->getOrCreatePerson($row, $organizationData['id'], $organizationData['owner_id']);
                        $deal = $this->createDeal($request, $personData['id'], $organizationData['id'], $organizationData['owner_id']);
                        array_push($deals, $deal);
                    }
                }
            }
            return response()->json(['message' => '✅ Job pushed successfully!', 'deal' => $deals], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Something went wrong!',
                'message' => '❌ Failed to push job'.$e->getMessage()
            ], 500);
        }
    }

    private function getOrCreateOrganization($builderName)
    {
        $apiUrl = env('PIPEDRIVE_API_BASE_URL') . "/organizations/search";
        $apiToken = env('PIPEDRIVE_API_TOKEN');

        $response = Http::get($apiUrl, [
            'api_token' => $apiToken,
            'term' => $builderName,
            'fields' => 'name',
            'exact_match' => true,
            'start' => 0,
            'limit' => 1,
        ]);

        $organizationId = $response->successful() ? ($response->json()['data']['items'][0]['item']['id'] ?? 0) : 0;
        $ownerId = 0;

        if ($organizationId == 0) {
            $createResponse = Http::post(env('PIPEDRIVE_API_BASE_URL') . "/organizations?api_token=$apiToken", [
                'name' => $builderName,
            ]);

            if ($createResponse->failed()) {
                throw new \Exception('Failed to create organization');
            }

            $responseData = $createResponse->json();
            $organizationId = $responseData['data']['id'] ?? null;
            $ownerId = $responseData['data']['owner_id']['id'] ?? null;
            Organization::create(['pipe_drive_org_id' => $organizationId, 'org_name' => $builderName, 'add_time' => now()]);
        }

        return ['id' => $organizationId, 'owner_id' => $ownerId];
    }

    private function getOrCreatePerson($row, $organizationId, $ownerId)
    {
        $apiUrl = env('PIPEDRIVE_API_BASE_URL') . "/persons/search";
        $apiToken = env('PIPEDRIVE_API_TOKEN');
        $email = $row['email'] ?? null;
        $personId = 0;

        if ($email) {
            $response = Http::get($apiUrl, [
                'api_token' => $apiToken,
                'fields' => 'email',
                'exact_match' => false,
                'organization_id' => $organizationId,
                'term' => $email,
            ]);

            if ($response->successful()) {
                $personId = $response->json()['data']['items'][0]['item']['id'] ?? 0;
            }
        }

        if ($personId == 0) {
            $createResponse = Http::post(env('PIPEDRIVE_API_BASE_URL') . "/persons?api_token=$apiToken", [
                'name' => $row['name'],
                'owner_id' => $ownerId,
                'org_id' => $organizationId,
                'email' => json_encode($row['email']),
                'phone' => json_encode($row['phone']),
            ]);

            if ($createResponse->failed()) {
                throw new \Exception('Failed to create person');
            }

            $responseData = $createResponse->json();
            $personId = $responseData['data']['id'] ?? null;
            Person::create(['pipe_drive_person_id' => $personId, 'person_name' => $row['name'], 'owner_id' => $ownerId, 'org_id' => $organizationId, 'person_email' => json_encode($row['email']), 'person_phone' => json_encode($row['phone'])]);
        }
        return ['id' => $personId];
    }

    private function createDeal($request, $personId, $organizationId, $ownerId)
    {
        $apiUrl = env('PIPEDRIVE_API_BASE_URL') . "/deals";
        $apiToken = env('PIPEDRIVE_API_TOKEN');

        $response = Http::post("$apiUrl?api_token=$apiToken", [
            'title' => $request->projectName,
            'value' => $request->projectValue,
            'currency' => 'AUD',
            'user_id' => $ownerId,
            'person_id' => $personId,
            'org_id' => $organizationId,
            'pipeline_id' => 1,
            'stage_id' => 1,
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to create deal');
        }

        $responseData = $response->json();
        $dealId = $responseData['data']['id'] ?? null;

        return Deal::create([
            'pipe_drive_deal_id' => $dealId,
            'deals_title' => $request->projectName,
            'deals_value' => $request->projectValue,
            'deals_currency' => 'AUD',
            'user_id' => $ownerId,
            'person_id' => $personId,
            'org_id' => $organizationId,
            'pipeline_id' => 1,
            'stage_id' => 1,
            'deals_status' => 'open',
            'description' => $request->description,
            'notes' => $request->notes,
            'address' => $request->address,
            'job_id'=> $request->projectId
        ]);
    }
}
