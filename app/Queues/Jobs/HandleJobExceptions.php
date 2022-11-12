<?php

namespace App\Queues\Jobs;

use \Exception;
use Illuminate\Support\Facades\Log;

trait HandleJobExceptions
{
    private function handleException(Exception $e)
    {
        Log::error($e);
        $maxAttempts = 11;
        
        if ($this->attempts() < $maxAttempts) {
            Log::notice('Job unsuccessful. Adding back to the queue for attempt number ' . ($this->attempts() + 1) . '.');
            switch( true ) {
                case ( $this->attempts() == 1 ): 
                    $this->release(now()->addMinutes(1));
                    break;
                case ( $this->attempts() == 2 ): 
                    $this->release(now()->addMinutes(4));
                    break;
                case ( $this->attempts() == 3 ): 
                    $this->release(now()->addMinutes(5));
                    break;
                case ( $this->attempts() == 4 ): 
                    $this->release(now()->addMinutes(10));
                    break;
                case ( $this->attempts() == 5 ): 
                    $this->release(now()->addMinutes(40));
                    break;
                case ( $this->attempts() == 6 ): 
                    $this->release(now()->addMinutes(60));
                    break;
                case ( $this->attempts() == 7 ): 
                    $this->release(now()->addMinutes(120));
                    break;
                case ( $this->attempts() == 8 ): 
                    $this->release(now()->addMinutes(240));
                    break;
                case ( $this->attempts() == 9 ): 
                    $this->release(now()->addMinutes(480));
                    break;
                case ( $this->attempts() == 10 ): 
                    $this->release(now()->addMinutes(480));
                    break;
                default: 
                    $this->release(now()->addMinutes(60));
            }            
        } else {
            $this->fail($e);
        }
    }
}