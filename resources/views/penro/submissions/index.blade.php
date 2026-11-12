<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - PMS</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/notifications.css') }}?v=5">
</head>
<body class="bg-light">
    @include('components.nav')

    <div class="d-flex">
        @include('components.sidebar')

        <main class="flex-grow-1 p-4">
            <div class="mb-4">
                <div>
                    <h2 class="mb-1"><i class="fa-solid fa-bell me-2"></i>Notifications</h2>
                    <p class="text-muted mb-0">Track PENRO or CENRO requested to edit the locked accomplishments.</p>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <div class="btn-group mb-3" role="group" aria-label="Submission status">
                @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'declined' => 'Declined'] as $value => $label)
                    <a href="{{ route('accomplishment-requests.index', ['status' => $value]) }}"
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
                                <th>Action</th>
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
                                <tr id="submission-{{ $submission->id }}">
                                    <td class="text-nowrap">
                                        {{ $submission->created_at->copy()->timezone('Asia/Manila')->format('M d, Y') }}
                                        <div class="small text-muted">{{ $submission->created_at->copy()->timezone('Asia/Manila')->format('g:i A') }}</div>
                                    </td>
                                    <td>
                                        <strong>{{ $submission->submitter?->name ?? 'Deleted user' }}</strong>
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
                                        @if($submission->reviewer)
                                            <div class="small text-muted mt-1">by {{ $submission->reviewer->name }}</div>
                                        @endif
                                        @if(filled($submission->review_notes))
                                            <div class="small text-danger mt-1">{{ $submission->review_notes }}</div>
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
                                        <div class="small mt-2 text-start text-dark">
                                            <strong>Reason for change:</strong> {{ $submission->request_reason ?: 'No reason provided (legacy request).' }}
                                        </div>
                                    </td>
                                    <td class="text-end" style="min-width: 210px">
                                        @if($submission->status === 'pending')
                                            <form method="POST" action="{{ route('accomplishment-requests.approve', $submission) }}" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-success btn-sm" type="submit"
                                                        onclick="return confirm('Approve and save this accomplishment?')">
                                                    <i class="fa fa-check me-1"></i>Approve
                                                </button>
                                            </form>
                                            <button class="btn btn-outline-danger btn-sm" type="button"
                                                    aria-controls="decline-{{ $submission->id }}" aria-expanded="false"
                                                    onclick="toggleDeclineForm({{ $submission->id }}, this)">
                                                <i class="fa fa-xmark me-1"></i>Decline
                                            </button>
                                            <div class="mt-2 text-start {{ (int) old('submission_id') === (int) $submission->id ? '' : 'd-none' }}"
                                                 id="decline-{{ $submission->id }}">
                                                <form method="POST" action="{{ route('accomplishment-requests.decline', $submission) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="submission_id" value="{{ $submission->id }}">
                                                    <textarea name="review_notes" class="form-control form-control-sm mb-2" rows="2"
                                                              maxlength="1000" required placeholder="Reason for declining">{{ (int) old('submission_id') === (int) $submission->id ? old('review_notes') : '' }}</textarea>
                                                    <button class="btn btn-danger btn-sm w-100" type="submit">Confirm decline</button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-muted small">Reviewed {{ optional($submission->reviewed_at)->diffForHumans() }}</span>
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
    <script>
        function toggleDeclineForm(submissionId, button) {
            const formContainer = document.getElementById(`decline-${submissionId}`);
            if (!formContainer) return;

            const willOpen = formContainer.classList.contains('d-none');
            formContainer.classList.toggle('d-none', !willOpen);
            button?.setAttribute('aria-expanded', willOpen ? 'true' : 'false');

            if (willOpen) {
                formContainer.querySelector('textarea')?.focus();
            }
        }
    </script>
</body>
</html>
