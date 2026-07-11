import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ProjectTaskController::index
* @see app/Http/Controllers/ProjectTaskController.php:17
* @route '/projects/{project}/tasks'
*/
export const index = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/projects/{project}/tasks',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ProjectTaskController::index
* @see app/Http/Controllers/ProjectTaskController.php:17
* @route '/projects/{project}/tasks'
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
* @see \App\Http\Controllers\ProjectTaskController::index
* @see app/Http/Controllers/ProjectTaskController.php:17
* @route '/projects/{project}/tasks'
*/
index.get = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectTaskController::index
* @see app/Http/Controllers/ProjectTaskController.php:17
* @route '/projects/{project}/tasks'
*/
index.head = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ProjectTaskController::store
* @see app/Http/Controllers/ProjectTaskController.php:39
* @route '/projects/{project}/tasks'
*/
export const store = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/projects/{project}/tasks',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ProjectTaskController::store
* @see app/Http/Controllers/ProjectTaskController.php:39
* @route '/projects/{project}/tasks'
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
* @see \App\Http\Controllers\ProjectTaskController::store
* @see app/Http/Controllers/ProjectTaskController.php:39
* @route '/projects/{project}/tasks'
*/
store.post = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ProjectTaskController::update
* @see app/Http/Controllers/ProjectTaskController.php:60
* @route '/projects/{project}/tasks/{task}'
*/
export const update = (args: { project: string | { id: string }, task: string | { id: string } } | [project: string | { id: string }, task: string | { id: string } ], options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put","patch"],
    url: '/projects/{project}/tasks/{task}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\ProjectTaskController::update
* @see app/Http/Controllers/ProjectTaskController.php:60
* @route '/projects/{project}/tasks/{task}'
*/
update.url = (args: { project: string | { id: string }, task: string | { id: string } } | [project: string | { id: string }, task: string | { id: string } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            project: args[0],
            task: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: typeof args.project === 'object'
        ? args.project.id
        : args.project,
        task: typeof args.task === 'object'
        ? args.task.id
        : args.task,
    }

    return update.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace('{task}', parsedArgs.task.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectTaskController::update
* @see app/Http/Controllers/ProjectTaskController.php:60
* @route '/projects/{project}/tasks/{task}'
*/
update.put = (args: { project: string | { id: string }, task: string | { id: string } } | [project: string | { id: string }, task: string | { id: string } ], options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\ProjectTaskController::update
* @see app/Http/Controllers/ProjectTaskController.php:60
* @route '/projects/{project}/tasks/{task}'
*/
update.patch = (args: { project: string | { id: string }, task: string | { id: string } } | [project: string | { id: string }, task: string | { id: string } ], options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ProjectTaskController::destroy
* @see app/Http/Controllers/ProjectTaskController.php:117
* @route '/projects/{project}/tasks/{task}'
*/
export const destroy = (args: { project: string | { id: string }, task: string | { id: string } } | [project: string | { id: string }, task: string | { id: string } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/projects/{project}/tasks/{task}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\ProjectTaskController::destroy
* @see app/Http/Controllers/ProjectTaskController.php:117
* @route '/projects/{project}/tasks/{task}'
*/
destroy.url = (args: { project: string | { id: string }, task: string | { id: string } } | [project: string | { id: string }, task: string | { id: string } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            project: args[0],
            task: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: typeof args.project === 'object'
        ? args.project.id
        : args.project,
        task: typeof args.task === 'object'
        ? args.task.id
        : args.task,
    }

    return destroy.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace('{task}', parsedArgs.task.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectTaskController::destroy
* @see app/Http/Controllers/ProjectTaskController.php:117
* @route '/projects/{project}/tasks/{task}'
*/
destroy.delete = (args: { project: string | { id: string }, task: string | { id: string } } | [project: string | { id: string }, task: string | { id: string } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\ProjectTaskController::updateProgress
* @see app/Http/Controllers/ProjectTaskController.php:94
* @route '/projects/{project}/tasks/{task}/progress'
*/
export const updateProgress = (args: { project: string | { id: string }, task: string | { id: string } } | [project: string | { id: string }, task: string | { id: string } ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateProgress.url(args, options),
    method: 'post',
})

updateProgress.definition = {
    methods: ["post"],
    url: '/projects/{project}/tasks/{task}/progress',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ProjectTaskController::updateProgress
* @see app/Http/Controllers/ProjectTaskController.php:94
* @route '/projects/{project}/tasks/{task}/progress'
*/
updateProgress.url = (args: { project: string | { id: string }, task: string | { id: string } } | [project: string | { id: string }, task: string | { id: string } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            project: args[0],
            task: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: typeof args.project === 'object'
        ? args.project.id
        : args.project,
        task: typeof args.task === 'object'
        ? args.task.id
        : args.task,
    }

    return updateProgress.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace('{task}', parsedArgs.task.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectTaskController::updateProgress
* @see app/Http/Controllers/ProjectTaskController.php:94
* @route '/projects/{project}/tasks/{task}/progress'
*/
updateProgress.post = (args: { project: string | { id: string }, task: string | { id: string } } | [project: string | { id: string }, task: string | { id: string } ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateProgress.url(args, options),
    method: 'post',
})

const ProjectTaskController = { index, store, update, destroy, updateProgress }

export default ProjectTaskController