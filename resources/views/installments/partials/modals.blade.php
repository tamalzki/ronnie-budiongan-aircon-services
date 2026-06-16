@php
    $ledgerRowsById = isset($ledgerRows)
        ? collect($ledgerRows)->keyBy(fn ($r) => $r['installment']->id)
        : collect();
    $fmt = fn ($n) => number_format((float) $n, 2);
@endphp

@foreach($sales as $s)
<div class="modal fade" id="editPlanModal{{ $s->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('installments.schedule.update', $s) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title"><i class="bi bi-sliders"></i> Edit Installment Plan — {{ $s->invoice_number }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    @php
                        $sKept      = $s->installmentPayments->where('amount_paid', '>', 0);
                        $sPaidLines = $sKept->count();
                        $sRemaining = max(0, round($s->total - $sKept->sum('amount'), 2));
                        $sCurMonths = $s->installment_months ?? $s->installmentPayments->count();
                    @endphp
                    <div class="row g-2 mb-3 text-center">
                        <div class="col-4">
                            <div class="card border-0 bg-primary bg-opacity-10 p-2">
                                <small class="text-muted">Inv. Amt.</small>
                                <strong class="small">₱{{ number_format($s->total, 2) }}</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card border-0 bg-success bg-opacity-10 p-2">
                                <small class="text-muted">Total Credit</small>
                                <strong class="small text-success">₱{{ number_format($s->installmentPayments->sum('amount_paid'), 2) }}</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card border-0 bg-danger bg-opacity-10 p-2">
                                <small class="text-muted">Outstanding</small>
                                <strong class="small text-danger">₱{{ number_format($sRemaining, 2) }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info border-0 mb-3" style="font-size:0.85rem;">
                        <i class="bi bi-info-circle"></i>
                        Installments with payments ({{ $sPaidLines }}) are kept as-is. The remaining months are
                        re-scheduled to cover the balance of <strong>₱{{ number_format($sRemaining, 2) }}</strong>.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Term (Total Months) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="installment_months"
                               value="{{ $sCurMonths }}" min="{{ max(1, $sPaidLines) }}" max="60" required>
                        <small class="text-muted">Includes the {{ $sPaidLines }} installment(s) already paid/partial. Min {{ max(1, $sPaidLines) }}, max 60.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Monthly/Daily Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" step="0.01" min="0.01" class="form-control" name="monthly_amount"
                                   value="{{ $s->installment_amount ?: '' }}"
                                   placeholder="Leave blank to split the balance evenly">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-save"></i> Update Plan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@foreach($installments->where('status', '!=', 'paid') as $installment)
@php
    $row = $ledgerRowsById->get($installment->id, []);
    $lineRemaining = round((float) $installment->amount - (float) $installment->amount_paid, 2);
    $billNo = $row['bill_no'] ?? ($installment->notes === 'Downpayment' ? 'Full DP' : $installment->installment_number);
@endphp
<div class="modal fade" id="payModal{{ $installment->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('installments.pay', $installment) }}" method="POST"
                  class="installment-pay-form" data-installment-id="{{ $installment->id }}">
                @csrf
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title">
                        <i class="bi bi-cash-coin"></i> Record Payment — Bill {{ $billNo }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    @if(!empty($row))
                        @include('installments.partials.modal-ledger-context', compact('installment', 'row', 'summary'))
                        <div class="modal-ledger-preview mb-3" id="payPreview{{ $installment->id }}"
                             data-contract="{{ $summary['original_contract_amount'] }}"
                             data-credit-before="{{ max(0, ($row['total_credit'] ?? 0) - ($row['amount_paid'] ?? 0)) }}">
                            <div class="row g-2 text-center" style="font-size:0.78rem;">
                                <div class="col-4">
                                    <div class="border rounded py-1 px-2 bg-light">
                                        <div class="text-muted">Total Credit After</div>
                                        <strong class="pay-preview-credit text-primary">—</strong>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded py-1 px-2 bg-light">
                                        <div class="text-muted">Outstanding After</div>
                                        <strong class="pay-preview-balance text-danger">—</strong>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded py-1 px-2 bg-light">
                                        <div class="text-muted">Rebate</div>
                                        <strong>{{ ($row['rebate'] ?? 0) > 0 ? $fmt($row['rebate']) : '—' }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                    <div class="row g-2 mb-3 text-center">
                        <div class="col-4">
                            <div class="card border-0 bg-primary bg-opacity-10 p-2">
                                <small class="text-muted">Ins. Date</small>
                                <strong class="small">{{ $installment->due_date->format('m/d/Y') }}</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card border-0 bg-warning bg-opacity-10 p-2">
                                <small class="text-muted">Ins. Mons.</small>
                                <strong class="small">₱{{ number_format($installment->amount, 2) }}</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card border-0 bg-danger bg-opacity-10 p-2">
                                <small class="text-muted">Line Balance</small>
                                <strong class="small text-danger">₱{{ number_format($lineRemaining, 2) }}</strong>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="border-top pt-3">
                        <h6 class="fw-semibold mb-3" style="font-size:0.85rem;">
                            <i class="bi bi-journal-check text-success"></i> Payment Entry
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Date Paid <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="paid_date" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">O.R. No. / Reference</label>
                                <input type="text" class="form-control font-monospace" name="reference_number"
                                       placeholder="Official receipt or reference number">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Amount Paid <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" step="0.01" class="form-control pay-amount-input"
                                           name="amount_paid" data-preview-id="payPreview{{ $installment->id }}"
                                           value="{{ $lineRemaining > 0 ? $lineRemaining : $installment->amount }}"
                                           min="0.01" required>
                                </div>
                                <small class="text-muted">Ins. Mons. due: ₱{{ number_format($installment->amount, 2) }} · Line balance: ₱{{ number_format($lineRemaining, 2) }}</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                                <select class="form-select pay-method-select" name="payment_method" required>
                                    <option value="">-- Select Method --</option>
                                    <option value="cash">💵 Cash</option>
                                    <option value="gcash">📱 GCash</option>
                                    <option value="bank_transfer">🏦 Bank Transfer</option>
                                    <option value="cheque">🧾 Cheque</option>
                                </select>
                            </div>
                            <div class="col-12 pay-cheque-fields" style="display:none;">
                                <label class="form-label fw-semibold">Bank / Account Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="cheque_bank" placeholder="e.g. BDO - Juan Dela Cruz">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Notes</label>
                                <textarea class="form-control" name="notes" rows="2" placeholder="Optional payment notes">{{ $installment->notes && !str_starts_with($installment->notes, 'Overflow') ? $installment->notes : '' }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info border-0 mt-3 mb-0" style="font-size:0.82rem;">
                        <i class="bi bi-info-circle"></i>
                        <strong>Flexible Payment:</strong> Amounts above the line balance overflow to the next unpaid installments. Total Credit and Outstanding Balance update on the ledger after saving.
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4">
                        <i class="bi bi-check-circle"></i> Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@foreach($installments->where('status', 'paid') as $installment)
@php
    $row = $ledgerRowsById->get($installment->id, []);
    $billNo = $row['bill_no'] ?? ($installment->notes === 'Downpayment' ? 'Full DP' : $installment->installment_number);
@endphp
<div class="modal fade" id="editModal{{ $installment->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('installments.update', $installment) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header bg-secondary text-white border-0">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil"></i> Edit Payment — Bill {{ $billNo }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    @if(!empty($row))
                        @include('installments.partials.modal-ledger-context', compact('installment', 'row', 'summary'))
                    @endif

                    <div class="border-top pt-3">
                        <h6 class="fw-semibold mb-3" style="font-size:0.85rem;">
                            <i class="bi bi-journal-check"></i> Payment Entry
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Ins. Mons.</label>
                                <input type="text" class="form-control bg-light" readonly
                                       value="₱{{ number_format($installment->amount, 2) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Total Amt. Due (Line)</label>
                                <input type="text" class="form-control bg-light" readonly
                                       value="₱{{ number_format($row['total_amount_due'] ?? $installment->amount, 2) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Total Credit (After)</label>
                                <input type="text" class="form-control bg-light" readonly
                                       value="₱{{ number_format($row['total_credit'] ?? $installment->amount_paid, 2) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Date Paid <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="paid_date"
                                       value="{{ $installment->paid_date ? $installment->paid_date->format('Y-m-d') : '' }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">O.R. No. / Reference</label>
                                <input type="text" class="form-control font-monospace" name="reference_number"
                                       value="{{ $installment->reference_number }}" placeholder="Official receipt number">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Amount Paid <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" step="0.01" class="form-control" name="amount_paid"
                                           value="{{ $installment->amount_paid }}" min="0.01"
                                           max="{{ $installment->amount }}" required>
                                </div>
                                <small class="text-muted">Maximum for this line: ₱{{ number_format($installment->amount, 2) }}</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Rebate</label>
                                <input type="text" class="form-control bg-light" readonly
                                       value="{{ ($row['rebate'] ?? 0) > 0 ? '₱' . $fmt($row['rebate']) : '—' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                                <select class="form-select pay-method-select" name="payment_method" required>
                                    <option value="cash"          {{ ($installment->payment_method ?? '') == 'cash'          ? 'selected' : '' }}>💵 Cash</option>
                                    <option value="gcash"         {{ ($installment->payment_method ?? '') == 'gcash'         ? 'selected' : '' }}>📱 GCash</option>
                                    <option value="bank_transfer" {{ ($installment->payment_method ?? '') == 'bank_transfer' ? 'selected' : '' }}>🏦 Bank Transfer</option>
                                    <option value="cheque"        {{ ($installment->payment_method ?? '') == 'cheque'        ? 'selected' : '' }}>🧾 Cheque</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Outstanding Balance</label>
                                <input type="text" class="form-control bg-light fw-bold text-danger" readonly
                                       value="₱{{ number_format($row['remaining_balance'] ?? ($installment->sale->balance ?? 0), 2) }}">
                            </div>
                            <div class="col-12 pay-cheque-fields" style="{{ ($installment->payment_method ?? '') == 'cheque' ? '' : 'display:none;' }}">
                                <label class="form-label fw-semibold">Bank / Account Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="cheque_bank"
                                       value="{{ $installment->cheque_bank }}" placeholder="e.g. BDO - Juan Dela Cruz">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Notes</label>
                                <textarea class="form-control" name="notes" rows="2">{{ $installment->notes }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-secondary px-4">
                        <i class="bi bi-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
