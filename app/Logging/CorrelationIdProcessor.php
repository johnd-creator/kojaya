<?php

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class CorrelationIdProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        if (app()->runningInConsole() || ! app()->bound('request')) {
            return $record;
        }

        $correlationId = request()->attributes->get('correlation_id')
            ?: request()->header('X-Correlation-ID');

        if ($correlationId) {
            $record->extra['correlation_id'] = $correlationId;
        }

        return $record;
    }
}
