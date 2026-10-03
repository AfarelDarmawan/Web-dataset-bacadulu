<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataAccessRequest;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccessRequestController extends Controller
{
    public function index(Request $request): View
    {
        $requests = DataAccessRequest::query()
            ->with([
                'user',
                'dataset.provider',
            ])
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where(
                    'status',
                    $request->string('status')
                )
            )
            ->when(
                $request->filled('q'),
                function ($query) use ($request): void {
                    $term = '%'
                        .$request->string('q')->trim()
                        .'%';

                    $query->where(
                        function ($inner) use ($term): void {
                            $inner
                                ->where(
                                    'request_number',
                                    'like',
                                    $term
                                )
                                ->orWhereHas(
                                    'user',
                                    fn ($user) => $user
                                        ->where(
                                            'name',
                                            'like',
                                            $term
                                        )
                                        ->orWhere(
                                            'email',
                                            'like',
                                            $term
                                        )
                                )
                                ->orWhereHas(
                                    'dataset',
                                    fn ($dataset) => $dataset
                                        ->where(
                                            'title',
                                            'like',
                                            $term
                                        )
                                );
                        }
                    );
                }
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view(
            'admin.requests.index',
            compact('requests')
        );
    }

    public function show(
        DataAccessRequest $accessRequest
    ): View {
        $accessRequest->load([
            'user',
            'dataset.provider',
            'dataset.variables',
            'reviewer',
        ]);

        return view(
            'admin.requests.show',
            compact('accessRequest')
        );
    }

    public function update(
        Request $request,
        DataAccessRequest $accessRequest
    ): RedirectResponse {
        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    'pending',
                    'approved',
                    'rejected',
                ]),
            ],
            'payment_status' => [
                'required',
                Rule::in([
                    'not_required',
                    'awaiting_invoice',
                    'awaiting_payment',
                    'payment_review',
                    'paid',
                    'failed',
                    'refunded',
                ]),
            ],
            'payment_reference' => [
                'nullable',
                'string',
                'max:120',
            ],
            'admin_note' => [
                'nullable',
                'string',
                'max:3000',
            ],
            'expires_days' => [
                'nullable',
                'integer',
                'min:1',
                'max:365',
            ],
        ]);

        if (! $accessRequest->requiresPayment()) {
            $validated['payment_status'] =
                'not_required';

            $validated['payment_reference'] =
                null;
        }

        if (
            $validated['status'] === 'approved'
            && $accessRequest->requiresPayment()
            && $validated['payment_status'] !== 'paid'
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Data berbayar hanya dapat disetujui setelah pembayaran ditandai terverifikasi.'
                );
        }

        if ($validated['status'] === 'approved') {
            $accessRequest->load([
                'dataset.provider',
                'user',
            ]);

            if (
                ! $accessRequest
                    ->dataset
                    ?->isPubliclyAvailable()
            ) {
                return back()->with(
                    'error',
                    'Permintaan tidak dapat disetujui karena dataset/provider tidak sedang dipublikasikan.'
                );
            }

            if (
                ! $accessRequest
                    ->user
                    ?->isActive()
            ) {
                return back()->with(
                    'error',
                    'Permintaan tidak dapat disetujui karena akun pemohon sedang nonaktif.'
                );
            }
        }

        $accessRequest->update([
            'status' => $validated['status'],
            'payment_status' =>
                $validated['payment_status'],
            'payment_reference' =>
                $validated['payment_reference']
                ?? null,
            'paid_at' =>
                $validated['payment_status'] === 'paid'
                    ? (
                        $accessRequest->paid_at
                        ?? now()
                    )
                    : null,
            'admin_note' =>
                $validated['admin_note']
                ?? null,
            'reviewed_by' =>
                $request->user()->id,
            'reviewed_at' => now(),
            'expires_at' =>
                $validated['status'] === 'approved'
                    ? now()->addDays(
                        (int) (
                            $validated['expires_days']
                            ?? 30
                        )
                    )
                    : null,
        ]);

        AuditService::record(
            'access_request.'
            .$validated['status'],
            $accessRequest,
            [
                'expires_at' =>
                    $accessRequest
                        ->expires_at
                        ?->toIso8601String(),
                'payment_status' =>
                    $accessRequest->payment_status,
            ]
        );

        return back()->with(
            'success',
            'Keputusan permintaan akses berhasil disimpan.'
        );
    }
}