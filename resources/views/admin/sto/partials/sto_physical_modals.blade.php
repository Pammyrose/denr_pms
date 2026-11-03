    <div id="saveSuccessAlertWrapper" class="position-fixed top-20 end-0 p-3 d-none"
        style="z-index: 1080; max-width: 420px;">
        <div class="alert alert-success alert-dismissible fade show shadow" role="alert" id="saveSuccessAlert">
            <strong>Success!</strong>
            <span id="saveSuccessMessage">Data saved successfully.</span>
            <button type="button" class="btn-close" aria-label="Close"></button>
        </div>
    </div>

    <div id="saveErrorAlertWrapper" class="position-fixed top-20 end-0 p-3 d-none"
        style="z-index: 1080; max-width: 420px;">
        <div class="alert alert-danger alert-dismissible fade show shadow" role="alert" id="saveErrorAlert">
            <strong>Error!</strong>
            <span id="saveErrorMessage">An error occurred.</span>
            <button type="button" class="btn-close" aria-label="Close"></button>
        </div>
    </div>

    <div class="modal fade" id="stoExcelPreviewModal" tabindex="-1" aria-labelledby="stoExcelPreviewModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-xl-down modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-success text-white">
                    <div>
                        <h5 class="modal-title" id="stoExcelPreviewModalLabel">Excel Import Preview</h5>
                        <div class="small opacity-75" id="stoExcelPreviewSubtitle">Review parsed sorting before import.</div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="stoExcelPreviewLoading" class="text-center py-5">
                        <div class="spinner-border text-success" role="status"></div>
                        <div class="fw-semibold mt-3">Reading Excel file...</div>
                    </div>

                    <div id="stoExcelPreviewContent" class="d-none">
                        <div class="row g-2 mb-3" id="stoExcelPreviewStats"></div>

                        <div id="stoExcelPreviewWarningPanel" class="alert alert-warning d-none" role="alert">
                            <div class="fw-bold mb-2">
                                <i class="fa fa-triangle-exclamation me-1"></i> Sorting warnings
                            </div>
                            <ul class="mb-0 ps-3" id="stoExcelPreviewWarnings"></ul>
                        </div>

                        <div class="table-responsive sto-preview-table-wrap">
                            <table class="table table-sm align-middle mb-0 sto-preview-table">
                                <thead>
                                    <tr>
                                        <th style="width: 72px;">Excel Row</th>
                                        <th>P/A/P Hierarchy</th>
                                        <th>Performance Indicator</th>
                                        <th style="width: 190px;">Offices</th>
                                    </tr>
                                </thead>
                                <tbody id="stoExcelPreviewRows"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="stoExcelConfirmImportBtn" disabled>
                        <i class="fa fa-file-import me-1"></i> Import Excel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteProgramConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    are you sure you want to delete?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteProgramBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deletePhysicalRowConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this row?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeletePhysicalRowBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Indicator Modal -->
    <div class="modal fade" id="addIndicatorModal" tabindex="-1" aria-labelledby="addIndicatorModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addIndicatorModalLabel">Add Performance Indicator</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <form id="addIndicatorForm" method="POST" action="{{ route('admin.sto_physical.indicators.store') }}"
                    data-update-route-template="{{ route('admin.sto_physical.indicators.update', ':id') }}"
                    data-delete-route-template="{{ route('admin.sto_physical.indicators.destroy', ':id') }}">
                    @csrf
                    <input type="hidden" id="indicator_id" name="indicator_id" value="">

                    <div class="modal-body">
                        <h4
                            class="font-extrabold text-2xl bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent pb-1 border-b-2 border-blue-200 inline-block">
                            P/A/P
                        </h4>
                        <div class="row g-3 mb-2">
                            @include('components.pap_program_fields')

                            <div class="col-12 col-md-6">
                                <label for="pap_activities" class="form-label fw-bold small">Activity</label>
                                <input type="text" id="pap_activities" class="form-control form-control-sm py-2"
                                    style="font-size: 0.875rem;" list="pap_activity_options" autocomplete="off">
                                <datalist id="pap_activity_options"></datalist>
                            </div> 

                            <div class="col-12 col-md-6">
                                <label for="pap_subactivities" class="form-label fw-bold small">Sub-activity</label>
                                <input type="text" id="pap_subactivities" class="form-control form-control-sm py-2"
                                    style="font-size: 0.875rem;" list="pap_subactivity_options" maxlength="255" autocomplete="off">
                                <datalist id="pap_subactivity_options"></datalist>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="pap_subsubactivities" class="form-label fw-bold small">Sub-Sub-activity</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" id="pap_subsubactivities" class="form-control form-control-sm py-2"
                                        style="font-size: 0.875rem;" list="pap_subsubactivity_options" maxlength="255" autocomplete="off">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="add_level_6_btn" 
                                        onclick="showNextPapLevel(6)" title="Add Sub-Sub-Sub-activity">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                                <datalist id="pap_subsubactivity_options"></datalist>
                            </div>

                            <div class="col-12 col-md-3">
                                <label for="pap_year" class="form-label fw-bold small">Year</label>
                                <input type="number" id="pap_year" class="form-control form-control-sm py-2"
                                    style="font-size: 0.875rem;" min="2000" max="2099" placeholder="YYYY"
                                    value="{{ (int) ($year ?? now()->year) }}">
                            </div>

                            <!-- Level 6: Sub-Sub-Sub-activity -->
                            <div class="col-12 col-md-6" id="pap_level_6_container" style="display: none;">
                                <label for="pap_level_6" class="form-label fw-bold small">Sub-Sub-Sub-activity</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" id="pap_level_6" class="form-control form-control-sm py-2"
                                        list="pap_level_6_options" style="font-size: 0.875rem;" maxlength="255" autocomplete="off">
                                    <datalist id="pap_level_6_options"></datalist>
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="add_level_7_btn" 
                                        onclick="showNextPapLevel(7)" title="Add Sub-Sub-Sub-Sub-activity">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Level 7: Sub-Sub-Sub-Sub-activity -->
                            <div class="col-12 col-md-6" id="pap_level_7_container" style="display: none;">
                                <label for="pap_level_7" class="form-label fw-bold small">Sub-Sub-Sub-Sub-activity</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" id="pap_level_7" class="form-control form-control-sm py-2"
                                        list="pap_level_7_options" style="font-size: 0.875rem;" maxlength="255" autocomplete="off">
                                    <datalist id="pap_level_7_options"></datalist>
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="add_level_8_btn" 
                                        onclick="showNextPapLevel(8)" title="Add Sub-Sub-Sub-Sub-Sub-activity">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
            </div>

                            <!-- Level 8: Sub-Sub-Sub-Sub-Sub-activity -->
                            <div class="col-12 col-md-6" id="pap_level_8_container" style="display: none;">
                                <label for="pap_level_8" class="form-label fw-bold small">Sub-Sub-Sub-Sub-Sub-activity</label>
                                <input type="text" id="pap_level_8" class="form-control form-control-sm py-2"
                                    list="pap_level_8_options" style="font-size: 0.875rem;" maxlength="255" autocomplete="off">
                                <datalist id="pap_level_8_options"></datalist>
                            </div>

                        </div>
                        <h4
                            class="font-extrabold text-2xl bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent pb-1 border-b-2 border-blue-200 inline-block">
                            Indicator
                        </h4>
                        <!-- Performance Indicator -->
                        <div class="mb-2">
                            <label for="modal_indicator_name" class="form-label fw-bold">Performance Indicator <label class="text-red-500 text-[10px]">(N/A if not applicable)</label></label>
                            <textarea type="text" name="indicator_name" id="modal_indicator_name"
                                class="form-control form-control-lg" placeholder="Input the performance indicator"
                                required></textarea>
                            @error('indicator_name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="use_indicator_type"
                                style="border-color: #000;">
                            <label class="form-check-label fw-bold" for="use_indicator_type">Choose indicator
                                type</label>
                        </div>

                        <div class="mb-2" id="indicator_type_wrapper" style="display: none;">
                            <label for="modal_indicator_type" class="form-label fw-bold">Type of Indicator</label>
                            <select name="indicator_type_id" id="modal_indicator_type"
                                class="form-control form-control-lg">
                                <option value="">-- Select Type --</option>
                                @foreach(($indicatorTypeOptions ?? []) as $typeOption)
                                    <option value="{{ data_get($typeOption, 'id') }}">{{ data_get($typeOption, 'name') }}
                                    </option>
                                @endforeach
                            </select>
                            @error('indicator_type_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Office -->
                        <div>
                            <label class="form-label fw-bold">Office / Unit</label>

                            <div class="mb-2">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input black-checkbox" type="checkbox" id="selectAllPenroRo">
                                    <label class="form-check-label fw-bold" for="selectAllPenroRo">Select All PENROs & RO</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input black-checkbox" type="checkbox" id="selectAllCenro">
                                    <label class="form-check-label fw-bold" for="selectAllCenro">Select All CENROs</label>
                                </div>
                            </div>

                            <div>
                                <div class="row row-cols-1 row-cols-md-3">
                                    @forelse(($offices ?? []) as $parent)
                                        <div class="col">
                                            <div class="form-check">
                                                <input class="form-check-input office-checkbox black-checkbox"
                                                    type="checkbox" value="{{ $parent->id }}" id="office_{{ $parent->id }}"
                                                    name="office_id[]">
                                                <label class="form-check-label" for="office_{{ $parent->id }}">
                                                    @php
                                                        $penroNames = ['benguet', 'ifugao', 'mt.province', 'apayao', 'abra', 'kalinga', 'ro'];
                                                        $isPenro = stripos($parent->name, 'PENRO') !== false;
                                                        foreach ($penroNames as $penro) {
                                                            if (stripos($parent->name, $penro) !== false) {
                                                                $isPenro = true;
                                                                break;
                                                            }
                                                        }
                                                    @endphp
                                                    @if($isPenro)
                                                        <strong>{{ $parent->name }}</strong>
                                                    @else
                                                        {{ $parent->name }}
                                                    @endif
                                                </label>
                                            </div>

                                            @foreach(($parent->children ?? []) as $child)
                                                <div class="form-check">
                                                    <input class="form-check-input office-checkbox black-checkbox"
                                                        type="checkbox" value="{{ $child->id }}" id="office_{{ $child->id }}"
                                                        name="office_id[]">
                                                    <label class="form-check-label" for="office_{{ $child->id }}">
                                                        @php
                                                            $penroNames = ['benguet', 'ifugao', 'mt.province', 'apayao', 'abra', 'kalinga'];
                                                            $isPenro = stripos($child->name, 'PENRO') !== false;
                                                            foreach ($penroNames as $penro) {
                                                                if (stripos($child->name, $penro) !== false) {
                                                                    $isPenro = true;
                                                                    break;
                                                                }
                                                            }
                                                        @endphp
                                                        @if($isPenro)
                                                            <strong>{{ $child->name }}</strong>
                                                        @else
                                                            {{ $child->name }}
                                                        @endif
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    @empty
                                        <div class="col-12">
                                            <p class="text-muted mb-0">No offices available</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                            @error('office_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

