<div class="col-12 col-md-6">
    <label for="pap_title" class="form-label fw-bold small">Project</label>
    <input type="text" id="pap_title" class="form-control form-control-sm py-2"
        list="pap_title_options" maxlength="255" autocomplete="off" required>
    <datalist id="pap_title_options">
        @foreach(($papTitles ?? []) as $existingTitle)
            <option value="{{ $existingTitle }}"></option>
        @endforeach
    </datalist>
    <input type="hidden" id="pap_project" value="N/A">
</div>
<div class="col-12 col-md-6">
    <label for="pap_program" class="form-label fw-bold small">Program</label>
    <div class="input-group input-group-sm">
        <input type="text" id="pap_program" class="form-control py-2" list="pap_program_options"
            maxlength="150" autocomplete="off" required>
        <button type="button" class="btn btn-outline-primary" id="add_subprogram_btn"
            onclick="pmsShowProgramLevel('subprogram')" title="Add Sub-program" aria-label="Add Sub-program"
            aria-controls="pap_subprogram_container" aria-expanded="false"><i class="fa fa-plus"></i></button>
    </div>
    <datalist id="pap_program_options"></datalist>
</div>
@foreach(['subprogram', 'subsubprogram', 'subsubsubprogram', 'subsubsubsubprogram', 'subsubsubsubsubprogram'] as $programIndex => $programField)
    @php
        $programLabel = ucfirst(str_repeat('sub-', $programIndex + 1).'program');
        $nextProgramField = str_repeat('sub', $programIndex + 2).'program';
        $nextProgramLabel = ucfirst(str_repeat('sub-', $programIndex + 2).'program');
    @endphp
    <div class="col-12 col-md-6" id="pap_{{ $programField }}_container" style="display: none;">
        <label for="pap_{{ $programField }}" class="form-label fw-bold small">{{ $programLabel }}</label>
        <div class="input-group input-group-sm">
            <input type="text" id="pap_{{ $programField }}" class="form-control py-2"
                list="pap_{{ $programField }}_options" maxlength="150" autocomplete="off">
            @if($programIndex < 4)
                <button type="button" class="btn btn-outline-primary" id="add_{{ $nextProgramField }}_btn"
                    onclick="pmsShowProgramLevel('{{ $nextProgramField }}')" title="Add {{ $nextProgramLabel }}"
                    aria-label="Add {{ $nextProgramLabel }}" aria-controls="pap_{{ $nextProgramField }}_container"
                    aria-expanded="false"><i class="fa fa-plus"></i></button>
            @endif
        </div>
        <datalist id="pap_{{ $programField }}_options"></datalist>
    </div>
@endforeach
