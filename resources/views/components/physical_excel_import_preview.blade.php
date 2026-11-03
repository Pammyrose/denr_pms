@php
    $excelDomPrefix = $sector;
    $excelPreviewClass = $sector . '-preview';
@endphp

<form method="POST" action="{{ route("admin.{$sector}_physical.import_excel") }}" enctype="multipart/form-data"
    class="d-inline-flex align-items-center" id="{{ $excelDomPrefix }}ExcelUploadForm"
    data-preview-url="{{ route("admin.{$sector}_physical.import_excel.preview") }}">
    @csrf
    <input type="hidden" name="year" value="{{ $year ?? now()->year }}">
    <input type="file" name="excel_file" id="{{ $excelDomPrefix }}ExcelUploadInput" class="d-none" accept=".xlsx"
        aria-label="Upload {{ $label }} Excel file">
    <button type="button" class="btn btn-success btn-sm" id="{{ $excelDomPrefix }}ExcelUploadBtn"
        onclick="document.getElementById('{{ $excelDomPrefix }}ExcelUploadInput').click()">
        <i class="fa fa-file-excel me-1"></i> Upload Excel
    </button>
</form>

<div class="modal fade" id="{{ $excelDomPrefix }}ExcelPreviewModal" tabindex="-1"
    aria-labelledby="{{ $excelDomPrefix }}ExcelPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen-xl-down modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <div>
                    <h5 class="modal-title" id="{{ $excelDomPrefix }}ExcelPreviewModalLabel">Excel Import Preview</h5>
                    <div class="small opacity-75" id="{{ $excelDomPrefix }}ExcelPreviewSubtitle">
                        Review parsed sorting before import.
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="{{ $excelDomPrefix }}ExcelPreviewLoading" class="text-center py-5">
                    <div class="spinner-border text-success" role="status"></div>
                    <div class="fw-semibold mt-3">Reading Excel file...</div>
                </div>

                <div id="{{ $excelDomPrefix }}ExcelPreviewContent" class="d-none">
                    <div class="row g-2 mb-3" id="{{ $excelDomPrefix }}ExcelPreviewStats"></div>

                    <div id="{{ $excelDomPrefix }}ExcelPreviewWarningPanel" class="alert alert-warning d-none"
                        role="alert">
                        <div class="fw-bold mb-2">
                            <i class="fa fa-triangle-exclamation me-1"></i> Sorting warnings
                        </div>
                        <ul class="mb-0 ps-3" id="{{ $excelDomPrefix }}ExcelPreviewWarnings"></ul>
                    </div>

                    <div class="table-responsive {{ $excelPreviewClass }}-table-wrap">
                        <table class="table table-sm align-middle mb-0 {{ $excelPreviewClass }}-table">
                            <thead>
                                <tr>
                                    <th style="width: 72px;">Excel Row</th>
                                    <th>P/A/P Hierarchy</th>
                                    <th>Performance Indicator</th>
                                    <th style="width: 190px;">Offices</th>
                                </tr>
                            </thead>
                            <tbody id="{{ $excelDomPrefix }}ExcelPreviewRows"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="{{ $excelDomPrefix }}ExcelConfirmImportBtn" disabled>
                    <i class="fa fa-file-import me-1"></i> Import Excel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const excelUploadForm = document.getElementById(@json($excelDomPrefix . 'ExcelUploadForm'));
        const excelUploadInput = document.getElementById(@json($excelDomPrefix . 'ExcelUploadInput'));
        const excelPreviewModalElement = document.getElementById(@json($excelDomPrefix . 'ExcelPreviewModal'));
        const excelPreviewModal = excelPreviewModalElement ? new bootstrap.Modal(excelPreviewModalElement) : null;
        const excelConfirmImportBtn = document.getElementById(@json($excelDomPrefix . 'ExcelConfirmImportBtn'));
        const excelPreviewLoading = document.getElementById(@json($excelDomPrefix . 'ExcelPreviewLoading'));
        const excelPreviewContent = document.getElementById(@json($excelDomPrefix . 'ExcelPreviewContent'));
        const excelPreviewStats = document.getElementById(@json($excelDomPrefix . 'ExcelPreviewStats'));
        const excelPreviewWarnings = document.getElementById(@json($excelDomPrefix . 'ExcelPreviewWarnings'));
        const excelPreviewWarningPanel = document.getElementById(@json($excelDomPrefix . 'ExcelPreviewWarningPanel'));
        const excelPreviewRows = document.getElementById(@json($excelDomPrefix . 'ExcelPreviewRows'));
        const excelPreviewSubtitle = document.getElementById(@json($excelDomPrefix . 'ExcelPreviewSubtitle'));
        const previewStatClass = @json($excelPreviewClass . '-stat');
        const previewLevelClass = @json($excelPreviewClass . '-level');

        const escapePreviewHtml = value => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        const setExcelPreviewLoading = (isLoading) => {
            if (excelPreviewLoading) excelPreviewLoading.classList.toggle('d-none', !isLoading);
            if (excelPreviewContent) excelPreviewContent.classList.toggle('d-none', isLoading);
        };

        const renderExcelPreview = (preview) => {
            const warningCount = Number(preview?.warning_count || 0);
            const rows = Array.isArray(preview?.rows) ? preview.rows : [];
            const warnings = Array.isArray(preview?.warnings) ? preview.warnings : [];

            if (excelPreviewSubtitle) {
                excelPreviewSubtitle.textContent = warningCount > 0
                    ? 'Review warnings before importing physical targets and accomplishments.'
                    : 'Physical targets and accomplishments are ready to import.';
            }

            if (excelPreviewStats) {
                const stats = preview?.import_type === 'both'
                    ? [
                        ['Physical Targets', preview?.target_imported ?? 0, 'text-bg-primary'],
                        ['Physical Accomplishments', preview?.accomplishment_imported ?? 0, 'text-bg-success'],
                        ['Skipped', preview?.skipped ?? 0, 'text-bg-secondary'],
                        ['Warnings', warningCount, warningCount > 0 ? 'text-bg-warning' : 'text-bg-light text-dark'],
                    ]
                    : [
                        ['Parsed Items', preview?.parsed_rows ?? 0, 'text-bg-primary'],
                        ['Office Rows', preview?.imported ?? 0, 'text-bg-success'],
                        ['Skipped', preview?.skipped ?? 0, 'text-bg-secondary'],
                        ['Warnings', warningCount, warningCount > 0 ? 'text-bg-warning' : 'text-bg-light text-dark'],
                    ];

                excelPreviewStats.innerHTML = stats.map(([label, value, badgeClass]) => `
                    <div class="col-6 col-md-3">
                        <div class="${previewStatClass}">
                            <span class="badge ${badgeClass}">${escapePreviewHtml(value)}</span>
                            <div>${escapePreviewHtml(label)}</div>
                        </div>
                    </div>
                `).join('');
            }

            if (excelPreviewWarnings && excelPreviewWarningPanel) {
                excelPreviewWarningPanel.classList.toggle('d-none', warnings.length === 0);
                excelPreviewWarnings.innerHTML = warnings.map(warning => {
                    const row = warning?.row ? `Row ${warning.row}: ` : '';
                    const levelClass = warning?.level === 'danger' ? 'text-danger fw-semibold' : '';
                    return `<li class="${levelClass}">${escapePreviewHtml(row + (warning?.message || 'Please review this row.'))}</li>`;
                }).join('');
            }

            if (excelPreviewRows) {
                if (rows.length === 0) {
                    excelPreviewRows.innerHTML = `
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No importable physical rows were found.</td>
                        </tr>
                    `;
                } else {
                    excelPreviewRows.innerHTML = rows.map(row => {
                        const hierarchyParts = window.pmsExcelPreviewHierarchy(row);
                        const hierarchy = hierarchyParts.length > 0
                            ? hierarchyParts.map((item, index) => `<div class="${previewLevelClass} level-${Math.min(index, 4)}">${escapePreviewHtml(item)}</div>`).join('')
                            : '<span class="text-muted">N/A</span>';
                        const offices = Array.isArray(row?.offices) && row.offices.length > 0
                            ? row.offices.slice(0, 8).map(office => `<span class="badge text-bg-light border">${escapePreviewHtml(office)}</span>`).join(' ')
                            : '<span class="text-muted">No matched office</span>';
                        const moreOffices = Array.isArray(row?.offices) && row.offices.length > 8
                            ? `<span class="badge text-bg-secondary">+${row.offices.length - 8}</span>`
                            : '';
                        const carBadge = row?.has_car_total
                            ? '<span class="badge text-bg-info ms-1">CAR total</span>'
                            : '';

                        return `
                            <tr>
                                <td class="fw-semibold">${escapePreviewHtml(row?.row || '')}</td>
                                <td>${hierarchy}</td>
                                <td>${escapePreviewHtml(row?.indicator || '')}</td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">${offices}${moreOffices}${carBadge}</div>
                                    <div class="small text-muted mt-1">${escapePreviewHtml(row?.office_count || 0)} office row(s)</div>
                                </td>
                            </tr>
                        `;
                    }).join('');
                }
            }

            if (excelConfirmImportBtn) {
                excelConfirmImportBtn.disabled = false;
            }
        };

        if (excelUploadForm && excelUploadInput && excelPreviewModal) {
            excelUploadInput.addEventListener('change', async function () {
                if (!this.files || this.files.length === 0) {
                    return;
                }

                setExcelPreviewLoading(true);
                if (excelConfirmImportBtn) excelConfirmImportBtn.disabled = true;
                if (excelPreviewRows) excelPreviewRows.innerHTML = '';
                if (excelPreviewWarnings) excelPreviewWarnings.innerHTML = '';
                excelPreviewModal.show();

                const previewUrl = excelUploadForm.dataset.previewUrl;
                const token = excelUploadForm.querySelector('input[name="_token"]')?.value || '';
                const formData = new FormData(excelUploadForm);

                try {
                    const response = await fetch(previewUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    const data = await response.json();
                    if (!response.ok || !data?.success) {
                        throw new Error(data?.message || 'Failed to preview Excel file.');
                    }

                    renderExcelPreview(data.preview || {});
                } catch (error) {
                    console.error(@json($label . ' Excel preview error:'), error);
                    if (excelPreviewSubtitle) {
                        excelPreviewSubtitle.textContent = 'Preview failed.';
                    }
                    if (excelPreviewStats) {
                        excelPreviewStats.innerHTML = '';
                    }
                    if (excelPreviewWarningPanel) {
                        excelPreviewWarningPanel.classList.remove('d-none');
                    }
                    if (excelPreviewWarnings) {
                        excelPreviewWarnings.innerHTML = `<li class="text-danger fw-semibold">${escapePreviewHtml(error?.message || 'Failed to preview Excel file.')}</li>`;
                    }
                    if (excelPreviewRows) {
                        excelPreviewRows.innerHTML = `
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Fix the Excel file or try uploading again.</td>
                            </tr>
                        `;
                    }
                    if (excelConfirmImportBtn) {
                        excelConfirmImportBtn.disabled = true;
                    }
                } finally {
                    setExcelPreviewLoading(false);
                }
            });

            excelPreviewModalElement?.addEventListener('hidden.bs.modal', function () {
                if (excelConfirmImportBtn?.dataset?.submitting === '1') {
                    return;
                }

                excelUploadInput.value = '';
            });
        }

        if (excelConfirmImportBtn && excelUploadForm) {
            excelConfirmImportBtn.addEventListener('click', function () {
                if (!excelUploadInput?.files || excelUploadInput.files.length === 0) {
                    if (typeof showTopRightErrorAlert === 'function') {
                        showTopRightErrorAlert('Please choose an Excel file first.');
                    }
                    return;
                }

                this.dataset.submitting = '1';
                this.disabled = true;
                this.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Importing...';
                excelUploadForm.submit();
            });
        }
    });
</script>
