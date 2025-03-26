<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\XeroAuthController;
use App\Models\XeroToken;
use App\Models\Contact;
use App\Models\XeroItem;
use Illuminate\Support\Carbon;
use App\Models\Invoice;

class InvoiceController extends Controller
{
    public function push_invoice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'projectId' => 'required',
            'projectName' => 'required',
            'taskName' => 'required',
            'amount' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $xeroAuthController = new XeroAuthController();
        $xeroAuthController->refreshToken();

        $token = XeroToken::first();
        if (!$token) {
            return response()->json(['error' => 'No token found. Please authorize first.'], 400);
        }

        $xeroAccessToken = $token->access_token;
        $xeroTenantId = $token->tenant_id;

        try {
            $projectId = $request->projectId;
            $projectName = $request->projectName;
            $taskName = $request->taskName;
            $amount = trim(preg_replace('/\s+/', '', $request->amount));
            $itemName = "$projectName | $taskName";
            $type = strpos($amount, '%') !== false ? 'percentage' : 'amount';
            $numericAmount = floatval(str_replace(['%', '$'], '', $amount));
            $phaseName = $request->phaseName;
            $taskNumber = $request->taskNumber;

            $taskId = $phaseName."-".$taskNumber;

            $invoiceExists = DB::table('invoices')
                ->where('pipe_drive_project_id', $projectId)
                ->where('pipe_drive_task_id', $taskId)
                ->exists();

            if ($invoiceExists) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Invoice for the task '{$taskName}' has already been created."
                ], 400);
            }

            $deal_data =  $this->getting_project_deatil_and_deal_data($type , $numericAmount , $projectId);
            $person_name = $deal_data['person_name'];
            $totalInvoiceValue = $deal_data['totalInvoiceValue'];
            // Xero Contact Creation
            $xeroContactId = $this->createXeroConatct($person_name , $xeroAccessToken , $xeroTenantId);
            // Xero Item Creation
            $xeroItemId = $this->createXeroItem($xeroAccessToken ,$itemName ,$totalInvoiceValue ,$xeroTenantId);
            if($xeroItemId > 0){
                XeroItem::create([
                    'code' => $itemName,
                    'name' => $itemName,
                    'is_sold' => true,
                    'unit_price' => $totalInvoiceValue,
                    'xero_item_id' => $xeroItemId,
                ]);
            }
            // Xero Invoice Creation
            $invoiceData = $this->createXeroInvoice($xeroContactId ,$itemName,$totalInvoiceValue,$xeroAccessToken,$xeroTenantId);
           $printLink ="";
            if($invoiceData){ 
                $currentDate = Carbon::now()->format('Y-m-d');
                $dueDate = Carbon::now()->addDays(45)->format('Y-m-d');
                Invoice::create([
                    'xero_invoice_id' => $invoiceData['InvoiceID'] ?? null,
                    'xero_invoice_url' => $invoiceData['Url'] ?? null,
                    'contact_id' => $xeroContactId,
                    'pipe_drive_project_id' => $projectId,
                    'pipe_drive_task_id' => $taskId,
                    'date' => $currentDate,
                    'due_date' => $dueDate,
                    'total_amount' => $totalInvoiceValue,
                    'status' => "AUTHORISED"
                 ]); 
                 $printLink = "https://go.xero.com/AccountsReceivable/View.aspx?invoiceID=" . $invoiceData['InvoiceID'];
            }
            return response()->json(['message' => 'Invoice created successfully','invoice_print_url'=>$printLink]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong', 'message' => $e->getMessage()], 500);
        } 
      }

    function getting_project_deatil_and_deal_data($type , $numericAmount , $projectId){
            $apiToken = env('PIPEDRIVE_API_TOKEN');
            $apiUrl = env('PIPEDRIVE_API_BASE_URL') . "/projects/$projectId";
            $response = Http::get($apiUrl, ['api_token' => $apiToken]);
            
            if ($response->failed()) {
                throw new \Exception('Failed to get project details');
            }
     
            $dealIdsArray = $response->json()['data']['deal_ids'] ?? [];
            $total_value_of_deal = 0;
            $person_name = "";
            
            foreach ($dealIdsArray as $deal_id) {
                $dealResponse = Http::get(env('PIPEDRIVE_API_BASE_URL') . "/deals/$deal_id", [
                    'api_token' => $apiToken,
                ]);
    
                if ($dealResponse->failed()) {
                    throw new \Exception("Failed to get deal detail for Deal ID: $deal_id");
                }
    
                 $dealData = $dealResponse->json()['data'] ?? null;
                if ($dealData) {
                    $total_value_of_deal += $dealData['value'] ?? 0;
                    $person_name = $dealData['person_name'] ?? 'Unknown';
                }
            }

            $totalInvoiceValue = $type === "percentage" ? ($numericAmount / 100) * $total_value_of_deal : $numericAmount;
            return [
                'totalInvoiceValue' => $totalInvoiceValue,
                'person_name' => $person_name
            ];
        } 

        function createXeroConatct($person_name , $xeroAccessToken , $xeroTenantId){
           $existingContactResponse = Http::withHeaders([
                'Authorization' => "Bearer $xeroAccessToken",
                'Xero-Tenant-Id' => $xeroTenantId,
                'Accept' => 'application/json',
            ])->get("https://api.xero.com/api.xro/2.0/Contacts", [
                'where' => 'EmailAddress=="' . $person_name . '"'
            ]);

            $existingContacts = $existingContactResponse->json();
            
            if (!empty($existingContacts['Contacts'])) {
                return $existingContacts['Contacts'][0]['ContactID'];
            }
            // If contact doesn't exist, create a new one
            $contactData = [
                "Contacts" => [[
                    "Name" => $person_name,
                    "FirstName" => $person_name,
                    "LastName" => "",
                    "EmailAddress" => filter_var($person_name, FILTER_VALIDATE_EMAIL) ? $person_name : null,
                    "Phones" => [["PhoneType" => "", "PhoneNumber" => ""]]
                ]]
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer $xeroAccessToken",
                'Xero-Tenant-Id' => $xeroTenantId,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post('https://api.xero.com/api.xro/2.0/Contacts', $contactData);

            $xeroResponse = $response->json(); 

            if($xeroResponse['Contacts'][0]['ContactID']  > 0){
                Contact::create([
                    'name' => $person_name,
                    'xero_contact_id' => $xeroResponse['Contacts'][0]['ContactID'] 
                ]);
            }
            return $xeroResponse['Contacts'][0]['ContactID'] ?? null;
        }

        function createXeroItem($xeroAccessToken ,$itemName ,$totalInvoiceValue ,$xeroTenantId){
            $response = Http::withToken($xeroAccessToken)
            ->withHeaders(['Xero-tenant-id' => $xeroTenantId])
            ->post('https://api.xero.com/api.xro/2.0/Items', [
                'Items' => [[
                    'Code' => preg_replace("/[^A-Za-z0-9 ]/", "",$itemName),
                    'Name' => preg_replace("/[^A-Za-z0-9 ]/", "",$itemName),
                    'IsSold' => true,
                    'SalesDetails' => ['UnitPrice' => $totalInvoiceValue]
                ]]
            ]);

                  $xeroResponse = $response->json();
            return   $xeroItemId = $xeroResponse['Items'][0]['ItemID'] ?? null;
        } 

        function createXeroInvoice($xeroContactId ,$itemName,$totalInvoiceValue,$xeroAccessToken,$xeroTenantId){
            $currentDate = Carbon::now()->format('Y-m-d');
            $dueDate = Carbon::now()->addDays(45)->format('Y-m-d');
            $payload = [
                "Invoices" => [[
                    "Type" => "ACCREC",
                    "Contact" => ["ContactID" => $xeroContactId],
                    "Date" => $currentDate,
                    "DueDate" => $dueDate,
                    "LineItems" => [[
                        "ItemCode" => preg_replace("/[^A-Za-z0-9 ]/", "",$itemName),
                        "Description" => preg_replace("/[^A-Za-z0-9 ]/", "",$itemName),
                        "Quantity" => 1,
                        "UnitAmount" => $totalInvoiceValue,
                        "AccountCode" => "200"
                    ]],
                    "Status" => "AUTHORISED"
                ]]
            ];

            $response = Http::withHeaders([
                "Authorization" => "Bearer $xeroAccessToken",
                "Xero-tenant-id" => $xeroTenantId,
                "Accept" => "application/json",
                "Content-Type" => "application/json",
            ])->post('https://api.xero.com/api.xro/2.0/Invoices', $payload); 

            

            if ($response->successful()) {
            return    $invoiceData = $response->json()['Invoices'][0] ?? null;
            } 
        }
      
    } 



