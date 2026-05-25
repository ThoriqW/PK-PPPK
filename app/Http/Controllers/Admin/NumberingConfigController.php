<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpsertNumberingRequest;
use App\Http\Resources\NumberingConfigResource;
use App\Models\AuditLog;
use App\Models\NumberingConfig;
use App\Services\AuditLogger;
use App\Services\NumberFormatRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NumberingConfigController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly NumberFormatRenderer $renderer,
    ) {
    }

    public function index()
    {
        return NumberingConfigResource::collection(NumberingConfig::orderByDesc('id')->get());
    }

    public function show(NumberingConfig $numbering): NumberingConfigResource
    {
        return new NumberingConfigResource($numbering);
    }

    public function store(UpsertNumberingRequest $request): NumberingConfigResource
    {
        $config = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $config = NumberingConfig::create(array_merge($data, [
                'updated_by' => $request->user()?->id,
            ]));
            if (! empty($data['is_active'])) {
                NumberingConfig::query()->where('id', '!=', $config->id)->update(['is_active' => false]);
            }
            return $config;
        });

        $this->audit->log(AuditLog::ACTION_NUMBERING_CHANGE, "Buat penomoran: {$config->name}", $config, $config->only([
            'format', 'current_number', 'padding', 'reset_policy', 'is_active',
        ]));

        return new NumberingConfigResource($config);
    }

    public function update(UpsertNumberingRequest $request, NumberingConfig $numbering): NumberingConfigResource
    {
        $before = $numbering->only(['format', 'current_number', 'padding', 'reset_policy', 'is_active']);

        DB::transaction(function () use ($request, $numbering) {
            $data = $request->validated();
            $numbering->fill($data);
            $numbering->updated_by = $request->user()?->id;
            $numbering->save();
            if (! empty($data['is_active'])) {
                NumberingConfig::query()->where('id', '!=', $numbering->id)->update(['is_active' => false]);
            }
        });

        $this->audit->log(AuditLog::ACTION_NUMBERING_CHANGE, "Ubah penomoran: {$numbering->name}", $numbering, [
            'before' => $before,
            'after'  => $numbering->only(['format', 'current_number', 'padding', 'reset_policy', 'is_active']),
        ]);

        return new NumberingConfigResource($numbering->fresh());
    }

    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'format'  => ['required', 'string', 'regex:/\{seq\}/'],
            'seq'     => ['required', 'integer', 'min:0'],
            'padding' => ['required', 'integer', 'min:1', 'max:6'],
            'opd'     => ['nullable', 'string'],
        ]);

        $now = now();
        return response()->json([
            'rendered' => $this->renderer->render($request->string('format'), [
                'seq'     => (int) $request->integer('seq'),
                'padding' => (int) $request->integer('padding'),
                'year'    => $now->year,
                'month'   => $now->month,
                'opd'     => $request->string('opd')->toString() ?: null,
            ]),
        ]);
    }
}
