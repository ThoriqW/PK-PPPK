<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\NumberingConfig;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Atomically reserves the next agreement number for a given numbering config,
 * honoring its reset_policy. Run inside an outer DB::transaction owned by the caller.
 *
 * @return array{number:string, sequence:int, config_id:int}
 */
class AgreementNumberGenerator
{
    public function __construct(private readonly NumberFormatRenderer $renderer)
    {
    }

    public function generate(NumberingConfig $config, Carbon $issuedAt, ?string $opdCode = null): array
    {
        if (! $config->is_active) {
            throw new BusinessException(
                'NUMBERING_CONFIG_INACTIVE',
                'Konfigurasi penomoran tidak aktif.',
                ['numbering_config_id' => $config->id],
            );
        }

        return DB::transaction(function () use ($config, $issuedAt, $opdCode) {
            // Lock the row to avoid race conditions when two admins generate concurrently.
            /** @var NumberingConfig $locked */
            $locked = NumberingConfig::query()->whereKey($config->id)->lockForUpdate()->firstOrFail();

            $year  = (int) $issuedAt->year;
            $month = (int) $issuedAt->month;

            $shouldReset = match ($locked->reset_policy) {
                NumberingConfig::RESET_NEVER   => false,
                NumberingConfig::RESET_YEARLY  => $locked->last_issued_year !== null && $locked->last_issued_year !== $year,
                NumberingConfig::RESET_MONTHLY => ($locked->last_issued_year !== $year) || ($locked->last_issued_month !== $month),
                default => false,
            };

            $next = $shouldReset ? 1 : ((int) $locked->current_number + 1);

            $rendered = $this->renderer->render($locked->format, [
                'seq'     => $next,
                'padding' => (int) $locked->padding,
                'year'    => $year,
                'month'   => $month,
                'opd'     => $opdCode,
            ]);

            $locked->current_number    = $next;
            $locked->last_issued_year  = $year;
            $locked->last_issued_month = $month;
            $locked->save();

            return [
                'number'    => $rendered,
                'sequence'  => $next,
                'config_id' => $locked->id,
            ];
        });
    }
}
