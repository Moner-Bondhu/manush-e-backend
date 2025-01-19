<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected $phoneNumber;
    protected $message;

    public function __construct($phoneNumber, $message)
    {
        if(strlen($phoneNumber) == 10){
            $phoneNumber = "0".$phoneNumber;
        }
        if(!str_starts_with($phoneNumber, "+88")){
            $phoneNumber = "+88".$phoneNumber;
        }
        $this->phoneNumber = $phoneNumber;
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $apiUrl = env('SSL_SMS_URL');
        $apiToken = env('SSL_SMS_API_TOKEN');
        $csms_id = env('SSL_SMS_CSMSID');
        $sid = env('SSL_SMS_SID');

        if(str_starts_with($this->phoneNumber, "+88")){
            $response = Http::post($apiUrl, [
                'api_token' => $apiToken,
                'msisdn' => $this->phoneNumber,
                'sid' => $sid,
                'csms_id' => $csms_id,
                'sms' => $this->message,
        ]);
        }
    }
}
