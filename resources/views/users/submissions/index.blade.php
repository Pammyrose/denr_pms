<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - PMS</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/notifications.css') }}?v=7">
</head>
<body class="bg-light">
    @include('components.nav')

    <div class="d-flex">
        @include('components.sidebar')

        <main class="flex-grow-1 p-4">
            <div class="mb-4">
                <h2 class="mb-1"><i class="fa-solid fa-bell me-2"></i>Notifications</h2>
                <p class="text-muted mb-0">Track Regional Office or administrator decisions on your locked-period change requests.</p>
            </div>

            <div class="btn-group mb-3" role="group" aria-label="Submission status">
                @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'declined' => 'Declined'] as $value => $label)
                    <a href="{{ route('notifications.index', ['status' => $value]) }}"
                       class="btn {{ $status === $value ? 'btn-primary' : 'btn-outline-primary' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div id="notificationResults">
            <div class="card shadow-sm border-0 notification-table-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 notification-table">
                        <thead>
                            <tr>
                                <th>Submitted</th>
                                <th>User / Office</th>
                                <th>Entry</th>
                                <th>Status</th>
                                <th>Values</th>
                                <th>PENRO Response</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($submissions as $submission)
                                @php
                                    $payload = (array) $submission->payload;
                                    $periodLabels = [
                                        'jan' => 'Jan', 'feb' => 'Feb', 'mar' => 'Mar', 'q1' => 'Q1',
                                        'apr' => 'Apr', 'may' => 'May', 'jun' => 'Jun', 'q2' => 'Q2',
                                        'jul' => 'Jul', 'aug' => 'Aug', 'sep' => 'Sep', 'q3' => 'Q3',
                                        'oct' => 'Oct', 'nov' => 'Nov', 'dec' => 'Dec', 'q4' => 'Q4',
                                        'annual_total' => 'Annual',
                                    ];
                                    $monthKeys = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
                                    $hasChangedPeriodMetadata = array_key_exists('_changed_periods', $payload);
                                    $changedPeriods = collect($payload['_changed_periods'] ?? [])
                                        ->filter(fn ($key) => isset($periodLabels[$key]))
                                        ->values();

                                    if (! $hasChangedPeriodMetadata && $changedPeriods->isEmpty()) {
                                        $changedPeriods = collect($monthKeys)
                                            ->filter(fn ($key) => (float) ($payload[$key] ?? 0) != 0.0)
                                            ->values();
                                    }

                                    $targetPayload = (array) ($submission->target_payload ?? []);
                                @endphp
                                <tr class="{{ in_array((int) $submission->id, $unreadIds, true) ? 'table-primary' : '' }}">
                                    <td class="text-nowrap">
                                        {{ $submission->created_at->copy()->timezone('Asia/Manila')->format('M d, Y') }}
                                        <div class="small text-muted">{{ $submission->created_at->copy()->timezone('Asia/Manila')->format('g:i A') }}</div>
                                    </td>
                                    <td>
                                        <strong>{{ $submission->submitter?->name ?? auth()->user()->name }}</strong>
                                        <div class="small text-muted">{{ $submission->office?->name ?? 'Unknown office' }}</div>
                                    </td>
                                    <td style="min-width: 220px">
                                        <span class="badge text-bg-secondary text-uppercase">{{ $submission->submission_type }}</span>
                                        <span class="badge text-bg-info text-uppercase">{{ $submission->sector }}</span>
                                        <div class="mt-1">{{ $submission->indicator?->name ?? 'Indicator #'.$submission->indicator_id }}</div>
                                        <div class="small text-muted">{{ $submission->program?->name ?: 'PAP #'.$submission->program_id }} · {{ $submission->year }}</div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $submission->status === 'approved' ? 'text-bg-success' : ($submission->status === 'declined' ? 'text-bg-danger' : 'text-bg-warning') }}">
                                            {{ ucfirst($submission->status) }}
                                        </span>
                                        @if(in_array((int) $submission->id, $unreadIds, true))
                                            <span class="badge text-bg-primary">New</span>
                                        @endif
                                        @if($submission->reviewed_at)
                                            <div class="small text-muted mt-1">{{ $submission->reviewed_at->copy()->timezone('Asia/Manila')->format('M d, Y g:i A') }}</div>
                                        @endif
                                    </td>
                                    <td class="notification-values-cell" style="min-width: 240px">
                                        @forelse($changedPeriods as $periodKey)
                                            <div class="notification-period-value border rounded bg-light">
                                                <strong class="d-block">{{ $periodLabels[$periodKey] }}</strong>
                                                <span class="badge text-bg-secondary me-1">
                                                    Target: {{ array_key_exists($periodKey, $targetPayload) ? number_format((float) $targetPayload[$periodKey], 0) : '—' }}
                                                </span>
                                                <span class="badge text-bg-primary">
                                                    User input: {{ number_format((float) ($payload[$periodKey] ?? 0), 0) }}
                                                </span>
                                            </div>
                                        @empty
                                            <span class="text-muted">No period value changed.</span>
                                        @endforelse
                                        @if(filled($payload['remarks'] ?? null))
                                            <div class="small mt-1 text-start"><strong>Remarks:</strong> {{ $payload['remarks'] }}</div>
                                        @endif
                                        <div class="small mt-2 text-start">
                                            <strong>Reason for change:</strong> {{ $submission->request_reason ?: 'No reason provided (legacy request).' }}
                                        </div>
                                    </td>
                                    <td style="min-width: 280px">
                                        @if($submission->status === 'declined')
                                            <div class="alert alert-danger py-2 px-3 mb-0">
                                                <strong class="d-block"><i class="fa-solid fa-xmark-circle me-1"></i>Reason for decline</strong>
                                                <span>{{ $submission->review_notes ?: 'No reason was provided.' }}</span>
                                                @if($submission->reviewer)
                                                    <div class="small mt-1">Reviewed by {{ $submission->reviewer->name }}</div>
                                                @endif
                                            </div>
                                        @elseif($submission->status === 'approved')
                                            <span class="text-success"><i class="fa-solid fa-check-circle me-1"></i>Approved and saved to official accomplishments.</span>
                                        @else
                                            <span class="text-muted"><i class="fa-regular fa-clock me-1"></i>Waiting for PENRO review.</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">No {{ $status }} accomplishment submissions.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">{{ $submissions->links() }}</div>
            </div>
        </main>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/notification-content.js') }}?v=1"></script>
</body>
</html>
