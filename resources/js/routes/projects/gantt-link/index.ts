import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\ProjectGanttController::store
* @see app/Http/Controllers/ProjectGanttController.php:70
* @route '/projects/{project}/gantt-link'
*/
export const store = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/projects/{project}/gantt-link',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ProjectGanttController::store
* @see app/Http/Controllers/ProjectGanttController.php:70
* @route '/projects/{project}/gantt-link'
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
* @see \App\Http\Controllers\ProjectGanttController::store
* @see app/Http/Controllers/ProjectGanttController.php:70
* @route '/projects/{project}/gantt-link'
*/
store.post = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ProjectGanttController::store
* @see app/Http/Controllers/ProjectGanttController.php:70
* @route '/projects/{project}/gantt-link'
*/
const storeForm = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ProjectGanttController::store
* @see app/Http/Controllers/ProjectGanttController.php:70
* @route '/projects/{project}/gantt-link'
*/
storeForm.post = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\ProjectGanttController::destroy
* @see app/Http/Controllers/ProjectGanttController.php:93
* @route '/projects/{project}/gantt-link/{link}'
*/
export const destroy = (args: { project: string | number | { id: string | number }, link: string | number } | [project: string | number | { id: string | number }, link: string | number ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/projects/{project}/gantt-link/{link}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\ProjectGanttController::destroy
* @see app/Http/Controllers/ProjectGanttController.php:93
* @route '/projects/{project}/gantt-link/{link}'
*/
destroy.url = (args: { project: string | number | { id: string | number }, link: string | number } | [project: string | number | { id: string | number }, link: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            project: args[0],
            link: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: typeof args.project === 'object'
        ? args.project.id
        : args.project,
        link: args.link,
    }

    return destroy.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace('{link}', parsedArgs.link.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectGanttController::destroy
* @see app/Http/Controllers/ProjectGanttController.php:93
* @route '/projects/{project}/gantt-link/{link}'
*/
destroy.delete = (args: { project: string | number | { id: string | number }, link: string | number } | [project: string | number | { id: string | number }, link: string | number ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\ProjectGanttController::destroy
* @see app/Http/Controllers/ProjectGanttController.php:93
* @route '/projects/{project}/gantt-link/{link}'
*/
const destroyForm = (args: { project: string | number | { id: string | number }, link: string | number } | [project: string | number | { id: string | number }, link: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ProjectGanttController::destroy
* @see app/Http/Controllers/ProjectGanttController.php:93
* @route '/projects/{project}/gantt-link/{link}'
*/
destroyForm.delete = (args: { project: string | number | { id: string | number }, link: string | number } | [project: string | number | { id: string | number }, link: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

const ganttLink = {
    store: Object.assign(store, store),
    destroy: Object.assign(destroy, destroy),
}

export default ganttLink