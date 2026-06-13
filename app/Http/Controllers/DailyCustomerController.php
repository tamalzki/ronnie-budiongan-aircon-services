<?php

namespace App\Http\Controllers;

use App\Models\DailyCustomer;
use App\Models\InventoryMovement;
use App\Models\Part;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DailyCustomerController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(DailyCustomer::class, 'daily_customer', ['except' => ['create', 'edit', 'show']]);
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->search);
        $status = $request->status;

        $entries = DailyCustomer::query()
            ->with('parts.part')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('service_type', 'like', "%{$search}%")
                        ->orWhere('other_service', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, ['paid', 'unpaid'], true), fn($q) => $q->where('status', $status))
            ->orderByDesc('service_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $todayCount = DailyCustomer::whereDate('service_date', now()->toDateString())->count();

        $unpaidStats = DailyCustomer::where('status', 'unpaid')
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(amount), 0) as amt')
            ->first();

        $paidStats = DailyCustomer::where('status', 'paid')
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(amount), 0) as amt')
            ->first();

        $unpaidCount  = (int) ($unpaidStats->cnt ?? 0);
        $unpaidAmount = (float) ($unpaidStats->amt ?? 0);
        $paidCount    = (int) ($paidStats->cnt ?? 0);
        $paidAmount   = (float) ($paidStats->amt ?? 0);

        $serviceTypes = Service::where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->push('Others')
            ->all();

        $partsJson = Part::with('product.pairedProduct')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Part $part) => [
                'id'                 => $part->id,
                'name'               => $part->name,
                'cost'               => (float) $part->cost,
                'linked_model_label' => $part->linked_model_label,
                'stock_quantity'     => $part->stock_quantity,
            ])
            ->values()
            ->all();

        return view('daily-customers.index', compact(
            'entries', 'search', 'status',
            'todayCount', 'unpaidCount', 'unpaidAmount', 'paidCount', 'paidAmount',
            'serviceTypes', 'partsJson'
        ));
    }

    public function store(Request $request)
    {
        $validated = $this->validateEntry($request);
        $parts = $validated['parts'] ?? [];
        unset($validated['parts']);

        $validated['user_id'] = auth()->id();

        DB::transaction(function () use ($validated, $parts) {
            $entry = DailyCustomer::create($validated);
            $this->syncParts($entry, $parts);
        });

        return redirect()->route('daily-customers.index')
            ->with('success', 'Daily customer entry added.');
    }

    public function update(Request $request, DailyCustomer $daily_customer)
    {
        $validated = $this->validateEntry($request);
        $parts = $validated['parts'] ?? [];
        unset($validated['parts']);

        DB::transaction(function () use ($daily_customer, $validated, $parts) {
            $daily_customer->update($validated);
            $this->syncParts($daily_customer, $parts);
        });

        return redirect()->route('daily-customers.index')
            ->with('success', 'Daily customer entry updated.');
    }

    public function updateStatus(Request $request, DailyCustomer $daily_customer)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['paid', 'unpaid'])],
        ]);

        $daily_customer->update(['status' => $validated['status']]);

        return redirect()->route('daily-customers.index')
            ->with('success', 'Marked as ' . ucfirst($validated['status']) . '.');
    }

    public function destroy(DailyCustomer $daily_customer)
    {
        DB::transaction(function () use ($daily_customer) {
            InventoryMovement::where('reference_type', 'DailyCustomer')
                ->where('reference_id', $daily_customer->id)
                ->delete();

            $daily_customer->parts()->delete();
            $daily_customer->delete();
        });

        return redirect()->route('daily-customers.index')
            ->with('success', 'Daily customer entry deleted.');
    }

    private function validateEntry(Request $request): array
    {
        $serviceNames = Service::pluck('name')->push('Others')->all();

        $validated = $request->validate([
            'customer_name'           => ['required', 'string', 'max:255'],
            'service_type'            => ['required', Rule::in($serviceNames)],
            'other_service'           => ['nullable', 'string', 'max:255', Rule::requiredIf(fn () => $request->service_type === 'Others')],
            'amount'                  => ['nullable', 'numeric', 'min:0'],
            'status'                  => ['required', Rule::in(['paid', 'unpaid'])],
            'service_date'            => ['required', 'date'],
            'notes'                   => ['nullable', 'string'],
            'parts'                   => ['nullable', 'array'],
            'parts.*.part_id'         => ['required', 'distinct', 'exists:parts,id'],
            'parts.*.quantity'        => ['required', 'integer', 'min:1'],
            'parts_included_in_price' => [Rule::requiredIf(fn () => ! empty($request->parts)), 'nullable', 'boolean'],
        ]);

        $validated['amount'] = $validated['amount'] ?? 0;

        if ($validated['service_type'] === 'Others') {
            $validated['service_type'] = trim($validated['other_service']);
        }

        $validated['other_service'] = null;

        $validated['parts_included_in_price'] = empty($validated['parts']) ? null : $validated['parts_included_in_price'];

        $this->syncServiceCatalog($validated['service_type'], $validated['amount']);

        return $validated;
    }

    /**
     * Replace the parts used on this entry, restoring stock for the
     * previous usage (via deleted inventory movements) before applying
     * the new selection.
     */
    private function syncParts(DailyCustomer $entry, array $parts): void
    {
        InventoryMovement::where('reference_type', 'DailyCustomer')
            ->where('reference_id', $entry->id)
            ->delete();

        $entry->parts()->delete();

        foreach ($parts as $partLine) {
            $part = Part::find($partLine['part_id']);
            $qty  = (int) $partLine['quantity'];

            $entry->parts()->create([
                'part_id'   => $part->id,
                'quantity'  => $qty,
                'unit_cost' => $part->cost,
            ]);

            $stockBefore = $part->stock_quantity;

            InventoryMovement::create([
                'part_id'        => $part->id,
                'type'           => 'stock_out',
                'quantity'       => $qty,
                'stock_before'   => $stockBefore,
                'stock_after'    => $stockBefore - $qty,
                'reference_type' => 'DailyCustomer',
                'reference_id'   => $entry->id,
                'notes'          => "Used on Daily Customer: {$entry->customer_name} ({$entry->service_label})",
                'user_id'        => auth()->id(),
            ]);
        }
    }

    /**
     * Keep the Services catalog in sync with Daily Customer entries: a
     * custom "Others" description becomes a reusable service, and the
     * default price reflects the most recently charged amount.
     */
    private function syncServiceCatalog(string $serviceType, float $amount): void
    {
        $service = Service::where('name', $serviceType)->first();

        if (! $service) {
            Service::create([
                'name'          => $serviceType,
                'default_price' => $amount,
                'is_active'     => true,
            ]);

            return;
        }

        if ($amount > 0) {
            $service->update(['default_price' => $amount]);
        }
    }
}
