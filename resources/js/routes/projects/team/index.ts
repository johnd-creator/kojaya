import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\ProjectTeamController::availability
* @see app/Http/Controllers/ProjectTeamController.php:15
* @route '/projects/{project}/team/availability'
*/
export const availability = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: availability.url(args, options),
    method: 'get',
})

availability.definition = {
    methods: ["get","head"],
    url: '/projects/{project}/team/availability',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ProjectTeamController::availability
* @see app/Http/Controllers/ProjectTeamController.php:15
* @route '/projects/{project}/team/availability'
*/
availability.url = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { project: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { project: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            project: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: typeof args.project === 'object'
        ? args.project.id
        : args.project,
    }

    return availability.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectTeamController::availability
* @see app/Http/Controllers/ProjectTeamController.php:15
* @route '/projects/{project}/team/availability'
*/
availability.get = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: availability.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectTeamController::availability
* @see app/Http/Controllers/ProjectTeamController.php:15
* @route '/projects/{project}/team/availability'
*/
availability.head = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: availability.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ProjectTeamController::availability
* @see app/Http/Controllers/ProjectTeamController.php:15
* @route '/projects/{project}/team/availability'
*/
const availabilityForm = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: availability.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectTeamController::availability
* @see app/Http/Controllers/ProjectTeamController.php:15
* @route '/projects/{project}/team/availability'
*/
availabilityForm.get = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: availability.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectTeamController::availability
* @see app/Http/Controllers/ProjectTeamController.php:15
* @route '/projects/{project}/team/availability'
*/
availabilityForm.head = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: availability.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

availability.form = availabilityForm

/**
* @see \App\Http\Controllers\ProjectTeamController::bulkAssign
* @see app/Http/Controllers/ProjectTeamController.php:140
* @route '/projects/{project}/team/bulk-assign'
*/
export const bulkAssign = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: bulkAssign.url(args, options),
    method: 'post',
})

bulkAssign.definition = {
    methods: ["post"],
    url: '/projects/{project}/team/bulk-assign',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ProjectTeamController::bulkAssign
* @see app/Http/Controllers/ProjectTeamController.php:140
* @route '/projects/{project}/team/bulk-assign'
*/
bulkAssign.url = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { project: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { project: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            project: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: typeof args.project === 'object'
        ? args.project.id
        : args.project,
    }

    return bulkAssign.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectTeamController::bulkAssign
* @see app/Http/Controllers/ProjectTeamController.php:140
* @route '/projects/{project}/team/bulk-assign'
*/
bulkAssign.post = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: bulkAssign.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ProjectTeamController::bulkAssign
* @see app/Http/Controllers/ProjectTeamController.php:140
* @route '/projects/{project}/team/bulk-assign'
*/
const bulkAssignForm = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: bulkAssign.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ProjectTeamController::bulkAssign
* @see app/Http/Controllers/ProjectTeamController.php:140
* @route '/projects/{project}/team/bulk-assign'
*/
bulkAssignForm.post = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: bulkAssign.url(args, options),
    method: 'post',
})

bulkAssign.form = bulkAssignForm

/**
* @see \App\Http\Controllers\ProjectTeamController::mobilization
* @see app/Http/Controllers/ProjectTeamController.php:120
* @route '/projects/{project}/team/{teamMember}/mobilization'
*/
export const mobilization = (args: { project: string | number, teamMember: string | { id: string } } | [project: string | number, teamMember: string | { id: string } ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: mobilization.url(args, options),
    method: 'post',
})

mobilization.definition = {
    methods: ["post"],
    url: '/projects/{project}/team/{teamMember}/mobilization',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ProjectTeamController::mobilization
* @see app/Http/Controllers/ProjectTeamController.php:120
* @route '/projects/{project}/team/{teamMember}/mobilization'
*/
mobilization.url = (args: { project: string | number, teamMember: string | { id: string } } | [project: string | number, teamMember: string | { id: string } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            project: args[0],
            teamMember: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: args.project,
        teamMember: typeof args.teamMember === 'object'
        ? args.teamMember.id
        : args.teamMember,
    }

    return mobilization.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace('{teamMember}', parsedArgs.teamMember.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectTeamController::mobilization
* @see app/Http/Controllers/ProjectTeamController.php:120
* @route '/projects/{project}/team/{teamMember}/mobilization'
*/
mobilization.post = (args: { project: string | number, teamMember: string | { id: string } } | [project: string | number, teamMember: string | { id: string } ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: mobilization.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ProjectTeamController::mobilization
* @see app/Http/Controllers/ProjectTeamController.php:120
* @route '/projects/{project}/team/{teamMember}/mobilization'
*/
const mobilizationForm = (args: { project: string | number, teamMember: string | { id: string } } | [project: string | number, teamMember: string | { id: string } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: mobilization.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ProjectTeamController::mobilization
* @see app/Http/Controllers/ProjectTeamController.php:120
* @route '/projects/{project}/team/{teamMember}/mobilization'
*/
mobilizationForm.post = (args: { project: string | number, teamMember: string | { id: string } } | [project: string | number, teamMember: string | { id: string } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: mobilization.url(args, options),
    method: 'post',
})

mobilization.form = mobilizationForm

const team = {
    availability: Object.assign(availability, availability),
    bulkAssign: Object.assign(bulkAssign, bulkAssign),
    mobilization: Object.assign(mobilization, mobilization),
}

export default team