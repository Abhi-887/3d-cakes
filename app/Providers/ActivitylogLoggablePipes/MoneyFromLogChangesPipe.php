<?php

declare(strict_types=1);

namespace App\Providers\ActivitylogLoggablePipes;

use Closure;
use Spatie\Activitylog\Contracts\LoggablePipe;
use Spatie\Activitylog\EventLogBag;

class MoneyFromLogChangesPipe implements LoggablePipe
{
    #[\Override]
    public function handle(EventLogBag $event, Closure $next): EventLogBag
    {
        if (isset($event->changes['attributes'])) {
            $event->changes['attributes'] = self::redactValues($event->changes['attributes']);
        }

        if (isset($event->changes['old'])) {
            $event->changes['old'] = self::redactValues($event->changes['old']);
        }

        return $next($event);
    }

    private static function redactValues(array $changes): array
    {
        foreach ($changes as $property => $value) {
            if ($value instanceof \Akaunting\Money\Money) {
                $changes[$property] = [
                    'amount' => $value->getAmount(),
                    'currency' => $value->getCurrency()->getCurrency(),
                ];
            }
        }

        return $changes;
    }
}
