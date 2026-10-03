<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\DataAccessRequest;
use App\Models\Dataset;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DataAccessRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');

        $allowedStatuses = [
            'pending',
            'approved',
            'rejected',
            'cancelled',
        ];

        $query = DataAccessRequest::query()
            ->where('user_id', $request->user()->id)
            ->with('dataset.provider');

        if (in_array($status, $allowedStatuses, true)) {
            $query->where('status', $status);
        } else {
            $status = '';
        }

        $requests = $query
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $requestCounts = DataAccessRequest::query()
            ->where('user_id', $request->user()->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $requestCounts['all'] = (int) $requestCounts->sum();

        return view(
            'user.requests.index',
            compact('requests', 'status', 'requestCounts')
        );
    }

    public function show(
        Request $request,
        DataAccessRequest $accessRequest
    ): View {
        abort_unless(
            $accessRequest->user_id === $request->user()->id,
            404
        );

        $accessRequest->load([
            'user',
            'dataset.provider',
            'dataset.variables',
        ]);

        return view(
            'user.requests.show',
            compact('accessRequest')
        );
    }

    public function store(
        Request $request,
        Dataset $dataset
    ): RedirectResponse {
        $dataset->loadMissing('provider');

        abort_unless(
            $dataset->isPubliclyAvailable(),
            404
        );

        abort_if(
            $dataset->isOpen(),
            422,
            'Dataset terbuka dapat langsung diekspor.'
        );

        $validated = $request->validate([
            'variable_ids' => [
                'required',
                'array',
                'min:1',
                'max:200',
            ],
            'variable_ids.*' => [
                'integer',
            ],
            'geographies' => [
                'nullable',
                'array',
                'max:500',
            ],
            'geographies.*' => [
                'string',
                'max:100',
            ],
            'periods' => [
                'nullable',
                'array',
                'max:500',
            ],
            'periods.*' => [
                'string',
                'max:30',
            ],
            'research_purpose' => [
                'required',
                'string',
                'min:20',
                'max:3000',
            ],
        ]);

        $variables = $dataset->variables()
            ->where('is_active', true)
            ->whereIn('id', $validated['variable_ids'])
            ->get();

        if (
            $variables->count()
            !== count(array_unique($validated['variable_ids']))
        ) {
            throw ValidationException::withMessages([
                'variable_ids' =>
                    'Pilihan variabel tidak valid untuk dataset ini.',
            ]);
        }

        $geographies = array_values(
            array_unique($validated['geographies'] ?? [])
        );

        $periods = array_values(
            array_unique($validated['periods'] ?? [])
        );

        $variableIds = $variables->pluck('id');

        if ($geographies !== []) {
            $validGeographies = $dataset->observations()
                ->whereIn(
                    'quality_status',
                    ['reviewed', 'verified']
                )
                ->whereIn(
                    'dataset_variable_id',
                    $variableIds
                )
                ->whereIn(
                    'geography_code',
                    $geographies
                )
                ->distinct()
                ->count('geography_code');

            if ($validGeographies !== count($geographies)) {
                throw ValidationException::withMessages([
                    'geographies' =>
                        'Pilihan '
                        .$dataset->entityLabelLower()
                        .' tidak valid untuk dataset ini.',
                ]);
            }
        }

        if ($periods !== []) {
            $validPeriods = $dataset->observations()
                ->whereIn(
                    'quality_status',
                    ['reviewed', 'verified']
                )
                ->whereIn(
                    'dataset_variable_id',
                    $variableIds
                )
                ->whereIn(
                    'period',
                    $periods
                )
                ->distinct()
                ->count('period');

            if ($validPeriods !== count($periods)) {
                throw ValidationException::withMessages([
                    'periods' =>
                        'Pilihan periode tidak valid untuk dataset ini.',
                ]);
            }
        }

        $observationQuery = $dataset->observations()
            ->whereIn(
                'quality_status',
                ['reviewed', 'verified']
            )
            ->whereIn(
                'dataset_variable_id',
                $variableIds
            );

        if ($geographies !== []) {
            $observationQuery->whereIn(
                'geography_code',
                $geographies
            );
        }

        if ($periods !== []) {
            $observationQuery->whereIn(
                'period',
                $periods
            );
        }

        $counts = (clone $observationQuery)
            ->selectRaw(
                'dataset_variable_id, COUNT(*) as total'
            )
            ->groupBy('dataset_variable_id')
            ->pluck('total', 'dataset_variable_id');

        $estimatedCells = (int) $counts->sum();

        if ($estimatedCells === 0) {
            throw ValidationException::withMessages([
                'variable_ids' =>
                    'Kombinasi variabel, '
                    .$dataset->entityLabelLower()
                    .', dan periode tidak memiliki observasi yang tersedia.',
            ]);
        }

        $estimatedPrice = $variables->sum(
            fn ($variable) =>
                ((int) ($counts[$variable->id] ?? 0))
                * (float) $variable->price_per_cell
        );

        $accessRequest = DataAccessRequest::create([
            'request_number' => $this->requestNumber(),
            'user_id' => $request->user()->id,
            'dataset_id' => $dataset->id,
            'variable_ids' => $variables
                ->pluck('id')
                ->values()
                ->all(),
            'geographies' => $geographies,
            'periods' => $periods,
            'research_purpose' =>
                $validated['research_purpose'],
            'estimated_cells' => $estimatedCells,
            'estimated_price' => $estimatedPrice,
            'payment_status' => $estimatedPrice > 0
                ? 'awaiting_invoice'
                : 'not_required',
            'status' => 'pending',
        ]);

        AuditService::record(
            'access_request.created',
            $accessRequest,
            [
                'dataset_id' => $dataset->id,
                'estimated_cells' => $estimatedCells,
                'estimated_price' => $estimatedPrice,
            ]
        );

        $message = $estimatedPrice > 0
            ? 'Permintaan berhasil dikirim. Hubungi WhatsApp BacaDulu untuk konfirmasi harga final dan pembayaran.'
            : 'Permintaan akses berhasil dikirim dan menunggu peninjauan admin.';

        return redirect()
            ->route(
                'user.requests.show',
                $accessRequest
            )
            ->with('success', $message);
    }

    private function requestNumber(): string
    {
        do {
            $number = 'BD-'
                .now()->format('Ymd')
                .'-'
                .Str::upper(Str::random(6));
        } while (
            DataAccessRequest::where(
                'request_number',
                $number
            )->exists()
        );

        return $number;
    }
}