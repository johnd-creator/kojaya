import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\AuditLogController::index
* @see app/Http/Controllers/AuditLogController.php:13
* @route '/api/audit-logs'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/audit-logs',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditLogController::index
* @see app/Http/Controllers/AuditLogController.php:13
* @route '/api/audit-logs'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditLogController::index
* @see app/Http/Controllers/AuditLogController.php:13
* @route '/api/audit-logs'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditLogController::index
* @see app/Http/Controllers/AuditLogController.php:13
* @route '/api/audit-logs'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditLogController::index
* @see app/Http/Controllers/AuditLogController.php:13
* @route '/api/audit-logs'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditLogController::index
* @see app/Http/Controllers/AuditLogController.php:13
* @route '/api/audit-logs'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditLogController::index
* @see app/Http/Controllers/AuditLogController.php:13
* @route '/api/audit-logs'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\AuditLogController::show
* @see app/Http/Controllers/AuditLogController.php:49
* @route '/api/audit-logs/{id}'
*/
export const show = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/audit-logs/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditLogController::show
* @see app/Http/Controllers/AuditLogController.php:49
* @route '/api/audit-logs/{id}'
*/
show.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return show.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditLogController::show
* @see app/Http/Controllers/AuditLogController.php:49
* @route '/api/audit-logs/{id}'
*/
show.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditLogController::show
* @see app/Http/Controllers/AuditLogController.php:49
* @route '/api/audit-logs/{id}'
*/
show.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditLogController::show
* @see app/Http/Controllers/AuditLogController.php:49
* @route '/api/audit-logs/{id}'
*/
const showForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditLogController::show
* @see app/Http/Controllers/AuditLogController.php:49
* @route '/api/audit-logs/{id}'
*/
showForm.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditLogController::show
* @see app/Http/Controllers/AuditLogController.php:49
* @route '/api/audit-logs/{id}'
*/
showForm.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\AuditLogController::history
* @see app/Http/Controllers/AuditLogController.php:58
* @route '/api/audit-logs/history/{type}/{id}'
*/
export const history = (args: { type: string | number, id: string | number } | [type: string | number, id: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: history.url(args, options),
    method: 'get',
})

history.definition = {
    methods: ["get","head"],
    url: '/api/audit-logs/history/{type}/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditLogController::history
* @see app/Http/Controllers/AuditLogController.php:58
* @route '/api/audit-logs/history/{type}/{id}'
*/
history.url = (args: { type: string | number, id: string | number } | [type: string | number, id: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            type: args[0],
            id: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        type: args.type,
        id: args.id,
    }

    return history.definition.url
            .replace('{type}', parsedArgs.type.toString())
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditLogController::history
* @see app/Http/Controllers/AuditLogController.php:58
* @route '/api/audit-logs/history/{type}/{id}'
*/
history.get = (args: { type: string | number, id: string | number } | [type: string | number, id: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: history.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditLogController::history
* @see app/Http/Controllers/AuditLogController.php:58
* @route '/api/audit-logs/history/{type}/{id}'
*/
history.head = (args: { type: string | number, id: string | number } | [type: string | number, id: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: history.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditLogController::history
* @see app/Http/Controllers/AuditLogController.php:58
* @route '/api/audit-logs/history/{type}/{id}'
*/
const historyForm = (args: { type: string | number, id: string | number } | [type: string | number, id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: history.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditLogController::history
* @see app/Http/Controllers/AuditLogController.php:58
* @route '/api/audit-logs/history/{type}/{id}'
*/
historyForm.get = (args: { type: string | number, id: string | number } | [type: string | number, id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: history.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditLogController::history
* @see app/Http/Controllers/AuditLogController.php:58
* @route '/api/audit-logs/history/{type}/{id}'
*/
historyForm.head = (args: { type: string | number, id: string | number } | [type: string | number, id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: history.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

history.form = historyForm

/**
* @see \App\Http\Controllers\AuditLogController::exportMethod
* @see app/Http/Controllers/AuditLogController.php:71
* @route '/api/audit-logs/export'
*/
export const exportMethod = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportMethod.url(options),
    method: 'get',
})

exportMethod.definition = {
    methods: ["get","head"],
    url: '/api/audit-logs/export',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditLogController::exportMethod
* @see app/Http/Controllers/AuditLogController.php:71
* @route '/api/audit-logs/export'
*/
exportMethod.url = (options?: RouteQueryOptions) => {
    return exportMethod.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditLogController::exportMethod
* @see app/Http/Controllers/AuditLogController.php:71
* @route '/api/audit-logs/export'
*/
exportMethod.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportMethod.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditLogController::exportMethod
* @see app/Http/Controllers/AuditLogController.php:71
* @route '/api/audit-logs/export'
*/
exportMethod.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: exportMethod.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuditLogController::exportMethod
* @see app/Http/Controllers/AuditLogController.php:71
* @route '/api/audit-logs/export'
*/
const exportMethodForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: exportMethod.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditLogController::exportMethod
* @see app/Http/Controllers/AuditLogController.php:71
* @route '/api/audit-logs/export'
*/
exportMethodForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: exportMethod.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuditLogController::exportMethod
* @see app/Http/Controllers/AuditLogController.php:71
* @route '/api/audit-logs/export'
*/
exportMethodForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: exportMethod.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

exportMethod.form = exportMethodForm

const AuditLogController = { index, show, history, exportMethod, export: exportMethod }

export default AuditLogController