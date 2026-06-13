import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\ProjectMilestoneController::index
* @see app/Http/Controllers/ProjectMilestoneController.php:13
* @route '/projects/{project}/milestones'
*/
export const index = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/projects/{project}/milestones',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ProjectMilestoneController::index
* @see app/Http/Controllers/ProjectMilestoneController.php:13
* @route '/projects/{project}/milestones'
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
* @see \App\Http\Controllers\ProjectMilestoneController::index
* @see app/Http/Controllers/ProjectMilestoneController.php:13
* @route '/projects/{project}/milestones'
*/
index.get = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectMilestoneController::index
* @see app/Http/Controllers/ProjectMilestoneController.php:13
* @route '/projects/{project}/milestones'
*/
index.head = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ProjectMilestoneController::index
* @see app/Http/Controllers/ProjectMilestoneController.php:13
* @route '/projects/{project}/milestones'
*/
const indexForm = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectMilestoneController::index
* @see app/Http/Controllers/ProjectMilestoneController.php:13
* @route '/projects/{project}/milestones'
*/
indexForm.get = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectMilestoneController::index
* @see app/Http/Controllers/ProjectMilestoneController.php:13
* @route '/projects/{project}/milestones'
*/
indexForm.head = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\ProjectMilestoneController::store
* @see app/Http/Controllers/ProjectMilestoneController.php:23
* @route '/projects/{project}/milestones'
*/
export const store = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/projects/{project}/milestones',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ProjectMilestoneController::store
* @see app/Http/Controllers/ProjectMilestoneController.php:23
* @route '/projects/{project}/milestones'
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
* @see \App\Http\Controllers\ProjectMilestoneController::store
* @see app/Http/Controllers/ProjectMilestoneController.php:23
* @route '/projects/{project}/milestones'
*/
store.post = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ProjectMilestoneController::store
* @see app/Http/Controllers/ProjectMilestoneController.php:23
* @route '/projects/{project}/milestones'
*/
const storeForm = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ProjectMilestoneController::store
* @see app/Http/Controllers/ProjectMilestoneController.php:23
* @route '/projects/{project}/milestones'
*/
storeForm.post = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\ProjectMilestoneController::update
* @see app/Http/Controllers/ProjectMilestoneController.php:35
* @route '/projects/{project}/milestones/{milestone}'
*/
export const update = (args: { project: string | number | { id: string | number }, milestone: string | number | { id: string | number } } | [project: string | number | { id: string | number }, milestone: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put","patch"],
    url: '/projects/{project}/milestones/{milestone}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\ProjectMilestoneController::update
* @see app/Http/Controllers/ProjectMilestoneController.php:35
* @route '/projects/{project}/milestones/{milestone}'
*/
update.url = (args: { project: string | number | { id: string | number }, milestone: string | number | { id: string | number } } | [project: string | number | { id: string | number }, milestone: string | number | { id: string | number } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            project: args[0],
            milestone: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: typeof args.project === 'object'
        ? args.project.id
        : args.project,
        milestone: typeof args.milestone === 'object'
        ? args.milestone.id
        : args.milestone,
    }

    return update.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace('{milestone}', parsedArgs.milestone.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectMilestoneController::update
* @see app/Http/Controllers/ProjectMilestoneController.php:35
* @route '/projects/{project}/milestones/{milestone}'
*/
update.put = (args: { project: string | number | { id: string | number }, milestone: string | number | { id: string | number } } | [project: string | number | { id: string | number }, milestone: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\ProjectMilestoneController::update
* @see app/Http/Controllers/ProjectMilestoneController.php:35
* @route '/projects/{project}/milestones/{milestone}'
*/
update.patch = (args: { project: string | number | { id: string | number }, milestone: string | number | { id: string | number } } | [project: string | number | { id: string | number }, milestone: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ProjectMilestoneController::update
* @see app/Http/Controllers/ProjectMilestoneController.php:35
* @route '/projects/{project}/milestones/{milestone}'
*/
const updateForm = (args: { project: string | number | { id: string | number }, milestone: string | number | { id: string | number } } | [project: string | number | { id: string | number }, milestone: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ProjectMilestoneController::update
* @see app/Http/Controllers/ProjectMilestoneController.php:35
* @route '/projects/{project}/milestones/{milestone}'
*/
updateForm.put = (args: { project: string | number | { id: string | number }, milestone: string | number | { id: string | number } } | [project: string | number | { id: string | number }, milestone: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ProjectMilestoneController::update
* @see app/Http/Controllers/ProjectMilestoneController.php:35
* @route '/projects/{project}/milestones/{milestone}'
*/
updateForm.patch = (args: { project: string | number | { id: string | number }, milestone: string | number | { id: string | number } } | [project: string | number | { id: string | number }, milestone: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

/**
* @see \App\Http\Controllers\ProjectMilestoneController::destroy
* @see app/Http/Controllers/ProjectMilestoneController.php:68
* @route '/projects/{project}/milestones/{milestone}'
*/
export const destroy = (args: { project: string | number | { id: string | number }, milestone: string | number | { id: string | number } } | [project: string | number | { id: string | number }, milestone: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/projects/{project}/milestones/{milestone}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\ProjectMilestoneController::destroy
* @see app/Http/Controllers/ProjectMilestoneController.php:68
* @route '/projects/{project}/milestones/{milestone}'
*/
destroy.url = (args: { project: string | number | { id: string | number }, milestone: string | number | { id: string | number } } | [project: string | number | { id: string | number }, milestone: string | number | { id: string | number } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            project: args[0],
            milestone: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: typeof args.project === 'object'
        ? args.project.id
        : args.project,
        milestone: typeof args.milestone === 'object'
        ? args.milestone.id
        : args.milestone,
    }

    return destroy.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace('{milestone}', parsedArgs.milestone.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectMilestoneController::destroy
* @see app/Http/Controllers/ProjectMilestoneController.php:68
* @route '/projects/{project}/milestones/{milestone}'
*/
destroy.delete = (args: { project: string | number | { id: string | number }, milestone: string | number | { id: string | number } } | [project: string | number | { id: string | number }, milestone: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\ProjectMilestoneController::destroy
* @see app/Http/Controllers/ProjectMilestoneController.php:68
* @route '/projects/{project}/milestones/{milestone}'
*/
const destroyForm = (args: { project: string | number | { id: string | number }, milestone: string | number | { id: string | number } } | [project: string | number | { id: string | number }, milestone: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ProjectMilestoneController::destroy
* @see app/Http/Controllers/ProjectMilestoneController.php:68
* @route '/projects/{project}/milestones/{milestone}'
*/
destroyForm.delete = (args: { project: string | number | { id: string | number }, milestone: string | number | { id: string | number } } | [project: string | number | { id: string | number }, milestone: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

const milestones = {
    index: Object.assign(index, index),
    store: Object.assign(store, store),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
}

export default milestones