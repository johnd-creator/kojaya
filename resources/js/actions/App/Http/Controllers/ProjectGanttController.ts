import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ProjectGanttController::getData
* @see app/Http/Controllers/ProjectGanttController.php:12
* @route '/projects/{project}/gantt-data'
*/
export const getData = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getData.url(args, options),
    method: 'get',
})

getData.definition = {
    methods: ["get","head"],
    url: '/projects/{project}/gantt-data',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ProjectGanttController::getData
* @see app/Http/Controllers/ProjectGanttController.php:12
* @route '/projects/{project}/gantt-data'
*/
getData.url = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return getData.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectGanttController::getData
* @see app/Http/Controllers/ProjectGanttController.php:12
* @route '/projects/{project}/gantt-data'
*/
getData.get = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getData.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectGanttController::getData
* @see app/Http/Controllers/ProjectGanttController.php:12
* @route '/projects/{project}/gantt-data'
*/
getData.head = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getData.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ProjectGanttController::getData
* @see app/Http/Controllers/ProjectGanttController.php:12
* @route '/projects/{project}/gantt-data'
*/
const getDataForm = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getData.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectGanttController::getData
* @see app/Http/Controllers/ProjectGanttController.php:12
* @route '/projects/{project}/gantt-data'
*/
getDataForm.get = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getData.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectGanttController::getData
* @see app/Http/Controllers/ProjectGanttController.php:12
* @route '/projects/{project}/gantt-data'
*/
getDataForm.head = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getData.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

getData.form = getDataForm

/**
* @see \App\Http\Controllers\ProjectGanttController::storeLink
* @see app/Http/Controllers/ProjectGanttController.php:70
* @route '/projects/{project}/gantt-link'
*/
export const storeLink = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeLink.url(args, options),
    method: 'post',
})

storeLink.definition = {
    methods: ["post"],
    url: '/projects/{project}/gantt-link',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ProjectGanttController::storeLink
* @see app/Http/Controllers/ProjectGanttController.php:70
* @route '/projects/{project}/gantt-link'
*/
storeLink.url = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return storeLink.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectGanttController::storeLink
* @see app/Http/Controllers/ProjectGanttController.php:70
* @route '/projects/{project}/gantt-link'
*/
storeLink.post = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeLink.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ProjectGanttController::storeLink
* @see app/Http/Controllers/ProjectGanttController.php:70
* @route '/projects/{project}/gantt-link'
*/
const storeLinkForm = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeLink.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ProjectGanttController::storeLink
* @see app/Http/Controllers/ProjectGanttController.php:70
* @route '/projects/{project}/gantt-link'
*/
storeLinkForm.post = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeLink.url(args, options),
    method: 'post',
})

storeLink.form = storeLinkForm

/**
* @see \App\Http\Controllers\ProjectGanttController::destroyLink
* @see app/Http/Controllers/ProjectGanttController.php:93
* @route '/projects/{project}/gantt-link/{link}'
*/
export const destroyLink = (args: { project: string | number | { id: string | number }, link: string | number } | [project: string | number | { id: string | number }, link: string | number ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroyLink.url(args, options),
    method: 'delete',
})

destroyLink.definition = {
    methods: ["delete"],
    url: '/projects/{project}/gantt-link/{link}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\ProjectGanttController::destroyLink
* @see app/Http/Controllers/ProjectGanttController.php:93
* @route '/projects/{project}/gantt-link/{link}'
*/
destroyLink.url = (args: { project: string | number | { id: string | number }, link: string | number } | [project: string | number | { id: string | number }, link: string | number ], options?: RouteQueryOptions) => {
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

    return destroyLink.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace('{link}', parsedArgs.link.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectGanttController::destroyLink
* @see app/Http/Controllers/ProjectGanttController.php:93
* @route '/projects/{project}/gantt-link/{link}'
*/
destroyLink.delete = (args: { project: string | number | { id: string | number }, link: string | number } | [project: string | number | { id: string | number }, link: string | number ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroyLink.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\ProjectGanttController::destroyLink
* @see app/Http/Controllers/ProjectGanttController.php:93
* @route '/projects/{project}/gantt-link/{link}'
*/
const destroyLinkForm = (args: { project: string | number | { id: string | number }, link: string | number } | [project: string | number | { id: string | number }, link: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroyLink.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ProjectGanttController::destroyLink
* @see app/Http/Controllers/ProjectGanttController.php:93
* @route '/projects/{project}/gantt-link/{link}'
*/
destroyLinkForm.delete = (args: { project: string | number | { id: string | number }, link: string | number } | [project: string | number | { id: string | number }, link: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroyLink.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroyLink.form = destroyLinkForm

const ProjectGanttController = { getData, storeLink, destroyLink }

export default ProjectGanttController