<?php namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;

class InsuranceController extends Controller
{
    // Apply a item to an array if not null
    private function ifApply ($item, $obj, $original) {
        if ($item !== null)
            return array_merge($original, $obj);
        return $original;
    }

    // Generic root api call
    private function authenticate ($type, $endpoint, $parameters) {
        $endpoint = env('ROOT_API_PROTOCOL').'://'.env('ROOT_API_SANDBOX').'/'.env('ROOT_INSURANCE_VERSION').'/'.env('ROOT_INSURANCE_CORE').'/'.$endpoint;
        $client = new Client();
        $response = $client->post($endpoint, [
            'auth' => [
                env('ROOT_INSURANCE_API_KEY'), ''
            ],
            $type => $parameters
        ]);
        return json_decode($response->getBody()->getContents());
    }


    // Step 1 - Generate a quote
    public function generateQuote($amount, $has_spouse, $number_of_children, $extended_family_ages) {
        // Call post method
        return $this->authenticate('form_params', 'quotes', [
            'type' => env('ROOT_MODULE'),
            'cover_amount' => $amount,
            'has_spouse' => ($has_spouse) ? 'true' : 'false',
            'number_of_children' => $number_of_children,
            'extended_family_ages' => $extended_family_ages
        ]);
    }

    // Step 2 - Create a policy holder
    public function createPolicyHolder ($type, $id, $country, $date_of_birth, $first_name, $last_name, $email = null, $cellphone = null, $app_data = null) {
        $obj = [
            'id' => [
                "type" => $type,
                "number" => $id,
                "country" => $country
            ],
            'date_of_birth' => $date_of_birth,
            'first_name' => $first_name,
            'last_name' => $last_name,
        ];
        // Apply optional parameters
        $obj = $this->ifApply($email, ['email' => $email], $obj);
        $obj = $this->ifApply($cellphone, ['cellphone' => $cellphone], $obj);
        $obj = $this->ifApply($app_data, ['app_data' => $app_data], $obj);

        // Call post method
        return $this->authenticate('json', 'policyholders', $obj);
    }


    // Step 3 - Create a application
    public function createApplication ($policyId, $monthlyPremium, $packageId) {
        // Call post method
        return $this->authenticate('form_params', 'applications', [
            'monthly_premium' => $monthlyPremium,
            'quote_package_id' => $packageId,
            'policyholder_id' => $policyId
        ]);
    }


    // Step 4 - Issue a policy
    public function issuePolicy ($applicationId) {
        // Call post method
        return $this->authenticate('json', 'policies', [
            'application_id' => $applicationId
        ]);
    }


    // Example call structure
    public function call (Request $request) {
        // $__quote = $this->generateQuote(2500000, false, 0, []);
        // $__policyholder = $this->createPolicyHolder("id", "6412167339085", "ZA", "19641010", "Melcom", "van Eeden");
        // $__application = $this->createApplication($__policyholder->{'policyholder_id'}, $__quote[0]->{'suggested_premium'}, $__quote[0]->{'quote_package_id'});
        // $__issuePolicy = $this->issuePolicy($__application->{'application_id'});
        // dd([
        //     $__quote,
        //     $__policyholder,
        //     $__application,
        //     $__issuePolicy
        // ]);

        $number = intval($request->get("result")['parameters']['number']);
        if ($number < 1000000 || $number > 5000000) {
            return response()->json([
                "speech" => "A",
                "displayText" => "A",
                "outputContext" => [
                    "name" => "cover_enquiry_amount",
                    'parameters' => array (
                        'number' => '30000',
                        'number.original' => '30000',
                    ),
                    "lifespan" => 1
                ],
                "type" => 0
            ], 200, ['Content-Type', 'application/json']);
        }
        else {
            $__quote = $this->generateQuote($number, false, 0, []);
            return response()->json([
                // "speech" => $request->get("result")['fulfillment']['speech'],
                // "displayText" => $request->get("result")['fulfillment']['speech'],
                "speech" => "Premium Amount: ".$__quote[0]->{'suggested_premium'},
                "displayText" => "Premium Amount: ".$__quote[0]->{'suggested_premium'},
                "outputContext" => [
                    "name" => "has_children_question",
                    'parameters' => array (
                        'number' => '30000',
                        'number.original' => '30000',
                    ),
                    "lifespan" => 1
                ],
                "type" => 0
            ], 200, ['Content-Type', 'application/json']);
        }
    }

    public function temp () {
        echo 'test';
    }
}




