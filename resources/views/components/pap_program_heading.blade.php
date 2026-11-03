<strong>{{ $program->title }}</strong>
@if(filled($program->program ?? '') && !in_array($normalizeGroupValue($program->program), ['n/a', 'na', 'not applicable'], true))
    <strong>{{ $program->program }}</strong>
@endif
@foreach(['subprogram', 'subsubprogram', 'subsubsubprogram', 'subsubsubsubprogram', 'subsubsubsubsubprogram'] as $programField)
    @if(filled($program->{$programField} ?? ''))
        <div class="text-sm mt-1 ml-6">{{ $program->{$programField} }}</div>
    @endif
@endforeach
@if(filled($program->project ?? '') && !in_array($normalizeGroupValue($program->project), ['n/a', 'na', 'not applicable'], true))
    <div class="text-sm text-gray-700 font-normal mt-2 ml-10">{{ $program->project }}</div>
@endif
