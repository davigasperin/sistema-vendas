<?php

namespace App\Http\Controllers;

use App\Actions\AddCashMovementAction;
use App\Actions\CloseCashShiftAction;
use App\Actions\OpenCashShiftAction;
use App\Enums\CashMovementType;
use App\Http\Requests\AddCashMovementRequest;
use App\Http\Requests\CloseCashShiftRequest;
use App\Http\Requests\OpenCashShiftRequest;
use App\Models\CashMovement;
use App\Models\CashShift;
use App\Models\Sale;
use App\Models\User;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CashShiftController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CashShift::class);

        $user = $request->user();
        $canAudit = $this->canAudit($user);
        $shifts = CashShift::query()
            ->with('user:id,name,email')
            ->withCount('sales')
            ->when(! $canAudit, fn ($query) => $query->where('user_id', $user->id))
            ->latest('opened_at')
            ->paginate(15)
            ->through(fn (CashShift $shift) => $this->shiftData($shift, $canAudit));
        $currentShift = $user->currentCashShift()?->load('movements.user');

        return Inertia::render('Cashier/Index', [
            'currentShift' => $currentShift ? $this->shiftData($currentShift, false, true) : null,
            'shifts' => $shifts,
            'canAudit' => $canAudit,
            'canOperate' => $user->canManageSales(),
        ]);
    }

    public function store(OpenCashShiftRequest $request, OpenCashShiftAction $action): RedirectResponse
    {
        $this->authorize('create', CashShift::class);

        try {
            $action(
                $request->user()->id,
                (float) $request->validated('initial_amount'),
                $request->validated('notes')
            );
        } catch (DomainException $e) {
            return back()->withErrors(['cash_shift' => $e->getMessage()]);
        }

        return back()->with('success', 'Caixa aberto com sucesso.');
    }

    public function movement(AddCashMovementRequest $request, AddCashMovementAction $action): RedirectResponse
    {
        $shift = $request->user()->currentCashShift();

        if (! $shift) {
            return back()->withErrors(['cash_shift' => 'Nenhum caixa aberto para receber a movimentação.']);
        }

        $this->authorize('addMovement', $shift);

        try {
            $action(
                $shift,
                $request->user()->id,
                CashMovementType::from($request->validated('type')),
                (float) $request->validated('amount'),
                $request->validated('reason')
            );
        } catch (DomainException $e) {
            return back()->withErrors(['cash_shift' => $e->getMessage()]);
        }

        return back()->with('success', 'Movimentação registrada com sucesso.');
    }

    public function close(CloseCashShiftRequest $request, CloseCashShiftAction $action): RedirectResponse
    {
        $shift = $request->user()->currentCashShift();

        if (! $shift) {
            return back()->withErrors(['cash_shift' => 'Nenhum caixa aberto para fechamento.']);
        }

        $this->authorize('close', $shift);

        try {
            $closedShift = $action(
                $shift,
                (float) $request->validated('reported_amount'),
                $request->validated('notes')
            );
        } catch (DomainException $e) {
            return back()->withErrors(['cash_shift' => $e->getMessage()]);
        }

        $differenceCents = (int) round(((float) $closedShift->difference) * 100);
        $message = $differenceCents === 0
            ? 'Caixa fechado sem diferenças.'
            : 'Caixa fechado com diferença de R$ '.number_format($differenceCents / 100, 2, ',', '.').'.';

        return back()->with('success', $message);
    }

    public function show(Request $request, CashShift $cashShift): Response
    {
        $this->authorize('view', $cashShift);

        $canAudit = $this->canAudit($request->user());
        $cashShift->load(['user:id,name,email', 'movements.user:id,name', 'sales.customer:id,name', 'sales.payments.paymentMethod:id,name']);

        return Inertia::render('Cashier/Show', [
            'shift' => $this->shiftData($cashShift, $canAudit, true),
            'canAudit' => $canAudit,
        ]);
    }

    private function canAudit(User $user): bool
    {
        return $user->isAdmin() || $user->role?->value === 'financial';
    }

    private function shiftData(CashShift $shift, bool $canAudit, bool $includeDetails = false): array
    {
        $data = [
            'id' => $shift->id,
            'user' => [
                'id' => $shift->user->id,
                'name' => $shift->user->name,
                'email' => $shift->user->email,
            ],
            'opened_at' => $shift->opened_at->toIso8601String(),
            'closed_at' => $shift->closed_at?->toIso8601String(),
            'initial_amount' => (float) $shift->initial_amount,
            'final_amount_reported' => $shift->final_amount_reported === null ? null : (float) $shift->final_amount_reported,
            'difference' => $shift->difference === null ? null : (float) $shift->difference,
            'status' => $shift->status->value,
            'notes' => $shift->notes,
            'sales_count' => $shift->sales_count,
        ];

        if ($canAudit) {
            $data['final_amount_expected'] = $shift->final_amount_expected === null ? null : (float) $shift->final_amount_expected;
        }

        if ($includeDetails) {
            $data['movements'] = $shift->movements->map(static fn (CashMovement $movement): array => [
                'id' => $movement->id,
                'user' => [
                    'id' => $movement->user->id,
                    'name' => $movement->user->name,
                ],
                'type' => $movement->type->value,
                'amount' => (float) $movement->amount,
                'reason' => $movement->reason,
                'created_at' => $movement->created_at?->toIso8601String() ?? '',
            ]);
            $data['sales'] = $shift->sales->map(static fn (Sale $sale): array => [
                'id' => $sale->id,
                'customer' => $sale->customer?->name,
                'total_amount' => (float) $sale->total_amount,
                'created_at' => $sale->created_at?->toIso8601String() ?? '',
            ]);
        }

        return $data;
    }
}
