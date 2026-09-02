<?php

/*
|--------------------------------------------------------------------------
| Project / SDLC settings
|--------------------------------------------------------------------------
*/

return [

    /*
     | Stage-gate enforcement. When false (default) the phase board is
     | advisory: advancing a phase with incomplete milestones only warns,
     | and a system_admin can always override. When true:
     |   - a phase cannot be advanced until every milestone tagged to the
     |     current phase is Done (no override), and
     |   - feature sets / tasks cannot be created under a milestone whose
     |     phase is later than the project's current phase.
     */
    'enforce_phase_gates' => (bool) env('ENFORCE_PHASE_GATES', false),

    // Auto-create scope items from the tender's scope_statement on promotion,
    // splitting on newlines / bullets / numbered lines.
    'seed_scope_from_tender' => (bool) env('SEED_SCOPE_FROM_TENDER', true),
];
