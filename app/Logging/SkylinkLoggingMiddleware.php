<?php

namespace App\Logging;

use DOMDocument;
use Exception;
use Illuminate\Support\Facades\Log;
use RetailExpress\SkyLink\Sdk\Apis\V2\Middleware;
use SoapFault;
use Throwable;
use ValueObjects\ValueObjectInterface;

class SkylinkLoggingMiddleware implements Middleware
{
    public function execute($request, &$response, SoapFault $soapFault = null, callable $next)
    {
        if (false === env('LOG_API_CALLS')) {
            return $next($request, $response, $soapFault);
        }

        try {
            $response = $next($request, $response, $soapFault);
            $this->logRequestAndResponse($request, $response);
            return $response;
        } catch (Throwable $e) {
            $this->logRequestResponseAndException($request, $response, $e);
            throw $e;
        }
    }

    private function logRequestAndResponse($request, $response)
    {
        Log::debug('Rex API request: ' . $this->formatXml($request));
        Log::debug('Rex API response: ' . $this->formatXml($response));
    }

    private function logRequestResponseAndException($request, $response, Exception $exception)
    {
        Log::debug('Rex API request: ' . $this->formatXml($request));
        Log::debug('Rex API response: ' . $this->formatXml($response));
        Log::debug('Rex API call exception', [
            'Name' => class_basename($exception),
            'Message' => $exception->getMessage(),
            'Where' => sprintf('%s @ Line %d', $exception->getFile(), $exception->getLine()),
            'Trace' => $exception->getTraceAsString()
        ]);
    }

    private function formatXml($response)
    {
        // We'll just check we have valid XML
        libxml_use_internal_errors(true);
        if (false === simplexml_load_string($response)) {
            return $response;
        }

        $domxml = new DOMDocument('1.0');
        $domxml->preserveWhiteSpace = false;
        $domxml->formatOutput = true;
        /* @var $xml SimpleXMLElement */
        $domxml->loadXML($response);
        $passwordResults = $domxml->getElementsByTagName('Password');
        if ($passwordResults->length > 0) {
            $password = $passwordResults[0];
            $password->parentNode->removeChild($password);
        }
        return $domxml->saveXML();
    }

    private function updateContext(array $context)
    {
        // Stringify value objects
        array_walk($context, function (&$value) {
            if ($value instanceof ValueObjectInterface) {
                $value = (string) $value;
            }
        });

        return $context;
    }
}
