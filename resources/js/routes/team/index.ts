import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\ProjectTeamController::index
* @see app/Http/Controllers/ProjectTeamController.php:50
* @route '/projects/{project}/team'
*/
export const index = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/projects/{project}/team',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ProjectTeamController::index
* @see app/Http/Controllers/ProjectTeamController.php:50
* @route '/projects/{project}/team'
*/
index.url = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return index.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectTeamController::index
* @see app/Http/Controllers/ProjectTeamController.php:50
* @route '/projects/{project}/team'
*/
index.get = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectTeamController::index
* @see app/Http/Controllers/ProjectTeamController.php:50
* @route '/projects/{project}/team'
*/
index.head = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ProjectTeamController::store
* @see app/Http/Controllers/ProjectTeamController.php:66
* @route '/projects/{project}/team'
*/
export const store = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/projects/{project}/team',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ProjectTeamController::store
* @see app/Http/Controllers/ProjectTeamController.php:66
* @route '/projects/{project}/team'
*/
store.url = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return store.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectTeamController::store
* @see app/Http/Controllers/ProjectTeamController.php:66
* @route '/projects/{project}/team'
*/
store.post = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ProjectTeamController::update
* @see app/Http/Controllers/ProjectTeamController.php:94
* @route '/projects/{project}/team/{team}'
*/
export const update = (args: { project: string | number, team: string | number } | [project: string | number, team: string | number ], options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put","patch"],
    url: '/projects/{project}/team/{team}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\ProjectTeamController::update
* @see app/Http/Controllers/ProjectTeamController.php:94
* @route '/projects/{project}/team/{team}'
*/
update.url = (args: { project: string | number, team: string | number } | [project: string | number, team: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            project: args[0],
            team: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: args.project,
        team: args.team,
    }

    return update.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace('{team}', parsedArgs.team.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectTeamController::update
* @see app/Http/Controllers/ProjectTeamController.php:94
* @route '/projects/{project}/team/{team}'
*/
update.put = (args: { project: string | number, team: string | number } | [project: string | number, team: string | number ], options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\ProjectTeamController::update
* @see app/Http/Controllers/ProjectTeamController.php:94
* @route '/projects/{project}/team/{team}'
*/
update.patch = (args: { project: string | number, team: string | number } | [project: string | number, team: string | number ], options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ProjectTeamController::destroy
* @see app/Http/Controllers/ProjectTeamController.php:112
* @route '/projects/{project}/team/{team}'
*/
export const destroy = (args: { project: string | number, team: string | number } | [project: string | number, team: string | number ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/projects/{project}/team/{team}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\ProjectTeamController::destroy
* @see app/Http/Controllers/ProjectTeamController.php:112
* @route '/projects/{project}/team/{team}'
*/
destroy.url = (args: { project: string | number, team: string | number } | [project: string | number, team: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            project: args[0],
            team: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: args.project,
        team: args.team,
    }

    return destroy.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace('{team}', parsedArgs.team.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectTeamController::destroy
* @see app/Http/Controllers/ProjectTeamController.php:112
* @route '/projects/{project}/team/{team}'
*/
destroy.delete = (args: { project: string | number, team: string | number } | [project: string | number, team: string | number ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

const team = {
    index: Object.assign(index, index),
    store: Object.assign(store, store),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
}

export default team