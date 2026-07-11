import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ProjectMilestoneController::index
* @see app/Http/Controllers/ProjectMilestoneController.php:13
* @route '/projects/{project}/milestones'
*/
export const index = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
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
index.url = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
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
index.get = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectMilestoneController::index
* @see app/Http/Controllers/ProjectMilestoneController.php:13
* @route '/projects/{project}/milestones'
*/
index.head = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ProjectMilestoneController::store
* @see app/Http/Controllers/ProjectMilestoneController.php:23
* @route '/projects/{project}/milestones'
*/
export const store = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
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
store.url = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
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
store.post = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ProjectMilestoneController::update
* @see app/Http/Controllers/ProjectMilestoneController.php:35
* @route '/projects/{project}/milestones/{milestone}'
*/
export const update = (args: { project: string | { id: string }, milestone: string | { id: string } } | [project: string | { id: string }, milestone: string | { id: string } ], options?: RouteQueryOptions): RouteDefinition<'put'> => ({
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
update.url = (args: { project: string | { id: string }, milestone: string | { id: string } } | [project: string | { id: string }, milestone: string | { id: string } ], options?: RouteQueryOptions) => {
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
update.put = (args: { project: string | { id: string }, milestone: string | { id: string } } | [project: string | { id: string }, milestone: string | { id: string } ], options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\ProjectMilestoneController::update
* @see app/Http/Controllers/ProjectMilestoneController.php:35
* @route '/projects/{project}/milestones/{milestone}'
*/
update.patch = (args: { project: string | { id: string }, milestone: string | { id: string } } | [project: string | { id: string }, milestone: string | { id: string } ], options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ProjectMilestoneController::destroy
* @see app/Http/Controllers/ProjectMilestoneController.php:68
* @route '/projects/{project}/milestones/{milestone}'
*/
export const destroy = (args: { project: string | { id: string }, milestone: string | { id: string } } | [project: string | { id: string }, milestone: string | { id: string } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
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
destroy.url = (args: { project: string | { id: string }, milestone: string | { id: string } } | [project: string | { id: string }, milestone: string | { id: string } ], options?: RouteQueryOptions) => {
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
destroy.delete = (args: { project: string | { id: string }, milestone: string | { id: string } } | [project: string | { id: string }, milestone: string | { id: string } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\ProjectMilestoneController::updateProgress
* @see app/Http/Controllers/ProjectMilestoneController.php:49
* @route '/projects/{project}/milestones/{milestone}/progress'
*/
export const updateProgress = (args: { project: string | { id: string }, milestone: string | { id: string } } | [project: string | { id: string }, milestone: string | { id: string } ], options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: updateProgress.url(args, options),
    method: 'patch',
})

updateProgress.definition = {
    methods: ["patch"],
    url: '/projects/{project}/milestones/{milestone}/progress',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ProjectMilestoneController::updateProgress
* @see app/Http/Controllers/ProjectMilestoneController.php:49
* @route '/projects/{project}/milestones/{milestone}/progress'
*/
updateProgress.url = (args: { project: string | { id: string }, milestone: string | { id: string } } | [project: string | { id: string }, milestone: string | { id: string } ], options?: RouteQueryOptions) => {
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

    return updateProgress.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace('{milestone}', parsedArgs.milestone.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectMilestoneController::updateProgress
* @see app/Http/Controllers/ProjectMilestoneController.php:49
* @route '/projects/{project}/milestones/{milestone}/progress'
*/
updateProgress.patch = (args: { project: string | { id: string }, milestone: string | { id: string } } | [project: string | { id: string }, milestone: string | { id: string } ], options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: updateProgress.url(args, options),
    method: 'patch',
})

const ProjectMilestoneController = { index, store, update, destroy, updateProgress }

export default ProjectMilestoneController