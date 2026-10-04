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
        $apiUrl = config('services.ssl_sms.url');
        $apiToken = config('services.ssl_sms.api_token');
        $csms_id = config('services.ssl_sms.csms_id');
        $sid = config('services.ssl_sms.sid');

        if (! $apiUrl || ! $apiToken || ! $sid) {
            throw new \RuntimeException('SMS provider is not configured.');
        }

        if(str_starts_with($this->phoneNumber, "+88")){
            Http::timeout(15)->post($apiUrl, [
                'api_token' => $apiToken,
                'msisdn' => $this->phoneNumber,
                'sid' => $sid,
                'csms_id' => $csms_id,
                'sms' => $this->message,
            ])->throw();
        }
    }
}
