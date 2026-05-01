import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ProjectDocumentController::index
* @see app/Http/Controllers/ProjectDocumentController.php:15
* @route '/projects/{project}/documents'
*/
export const index = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/projects/{project}/documents',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ProjectDocumentController::index
* @see app/Http/Controllers/ProjectDocumentController.php:15
* @route '/projects/{project}/documents'
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
* @see \App\Http\Controllers\ProjectDocumentController::index
* @see app/Http/Controllers/ProjectDocumentController.php:15
* @route '/projects/{project}/documents'
*/
index.get = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectDocumentController::index
* @see app/Http/Controllers/ProjectDocumentController.php:15
* @route '/projects/{project}/documents'
*/
index.head = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ProjectDocumentController::store
* @see app/Http/Controllers/ProjectDocumentController.php:25
* @route '/projects/{project}/documents'
*/
export const store = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/projects/{project}/documents',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ProjectDocumentController::store
* @see app/Http/Controllers/ProjectDocumentController.php:25
* @route '/projects/{project}/documents'
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
* @see \App\Http\Controllers\ProjectDocumentController::store
* @see app/Http/Controllers/ProjectDocumentController.php:25
* @route '/projects/{project}/documents'
*/
store.post = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ProjectDocumentController::destroy
* @see app/Http/Controllers/ProjectDocumentController.php:48
* @route '/projects/{project}/documents/{document}'
*/
export const destroy = (args: { project: string | number, document: string | { id: string } } | [project: string | number, document: string | { id: string } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/projects/{project}/documents/{document}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\ProjectDocumentController::destroy
* @see app/Http/Controllers/ProjectDocumentController.php:48
* @route '/projects/{project}/documents/{document}'
*/
destroy.url = (args: { project: string | number, document: string | { id: string } } | [project: string | number, document: string | { id: string } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            project: args[0],
            document: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: args.project,
        document: typeof args.document === 'object'
        ? args.document.id
        : args.document,
    }

    return destroy.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace('{document}', parsedArgs.document.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectDocumentController::destroy
* @see app/Http/Controllers/ProjectDocumentController.php:48
* @route '/projects/{project}/documents/{document}'
*/
destroy.delete = (args: { project: string | number, document: string | { id: string } } | [project: string | number, document: string | { id: string } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

const ProjectDocumentController = { index, store, destroy }

export default ProjectDocumentController