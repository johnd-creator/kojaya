import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\PosSyncApiController::catalog
* @see app/Http/Controllers/Api/V1/PosSyncApiController.php:18
* @route '/api/v1/pos/sync/catalog'
*/
export const catalog = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: catalog.url(options),
    method: 'get',
})

catalog.definition = {
    methods: ["get","head"],
    url: '/api/v1/pos/sync/catalog',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\PosSyncApiController::catalog
* @see app/Http/Controllers/Api/V1/PosSyncApiController.php:18
* @route '/api/v1/pos/sync/catalog'
*/
catalog.url = (options?: RouteQueryOptions) => {
    return catalog.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\PosSyncApiController::catalog
* @see app/Http/Controllers/Api/V1/PosSyncApiController.php:18
* @route '/api/v1/pos/sync/catalog'
*/
catalog.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: catalog.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\PosSyncApiController::catalog
* @see app/Http/Controllers/Api/V1/PosSyncApiController.php:18
* @route '/api/v1/pos/sync/catalog'
*/
catalog.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: catalog.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\PosSyncApiController::enqueue
* @see app/Http/Controllers/Api/V1/PosSyncApiController.php:31
* @route '/api/v1/pos/sync/enqueue'
*/
export const enqueue = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: enqueue.url(options),
    method: 'post',
})

enqueue.definition = {
    methods: ["post"],
    url: '/api/v1/pos/sync/enqueue',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\PosSyncApiController::enqueue
* @see app/Http/Controllers/Api/V1/PosSyncApiController.php:31
* @route '/api/v1/pos/sync/enqueue'
*/
enqueue.url = (options?: RouteQueryOptions) => {
    return enqueue.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\PosSyncApiController::enqueue
* @see app/Http/Controllers/Api/V1/PosSyncApiController.php:31
* @route '/api/v1/pos/sync/enqueue'
*/
enqueue.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: enqueue.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\PosSyncApiController::process
* @see app/Http/Controllers/Api/V1/PosSyncApiController.php:63
* @route '/api/v1/pos/sync/process/{idempotency_key}'
*/
export const process = (args: { idempotency_key: string | number } | [idempotency_key: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: process.url(args, options),
    method: 'post',
})

process.definition = {
    methods: ["post"],
    url: '/api/v1/pos/sync/process/{idempotency_key}',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\PosSyncApiController::process
* @see app/Http/Controllers/Api/V1/PosSyncApiController.php:63
* @route '/api/v1/pos/sync/process/{idempotency_key}'
*/
process.url = (args: { idempotency_key: string | number } | [idempotency_key: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { idempotency_key: args }
    }

    if (Array.isArray(args)) {
        args = {
            idempotency_key: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        idempotency_key: args.idempotency_key,
    }

    return process.definition.url
            .replace('{idempotency_key}', parsedArgs.idempotency_key.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\PosSyncApiController::process
* @see app/Http/Controllers/Api/V1/PosSyncApiController.php:63
* @route '/api/v1/pos/sync/process/{idempotency_key}'
*/
process.post = (args: { idempotency_key: string | number } | [idempotency_key: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: process.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\PosSyncApiController::processBatch
* @see app/Http/Controllers/Api/V1/PosSyncApiController.php:72
* @route '/api/v1/pos/sync/batch'
*/
export const processBatch = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: processBatch.url(options),
    method: 'post',
})

processBatch.definition = {
    methods: ["post"],
    url: '/api/v1/pos/sync/batch',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\PosSyncApiController::processBatch
* @see app/Http/Controllers/Api/V1/PosSyncApiController.php:72
* @route '/api/v1/pos/sync/batch'
*/
processBatch.url = (options?: RouteQueryOptions) => {
    return processBatch.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\PosSyncApiController::processBatch
* @see app/Http/Controllers/Api/V1/PosSyncApiController.php:72
* @route '/api/v1/pos/sync/batch'
*/
processBatch.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: processBatch.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\PosSyncApiController::status
* @see app/Http/Controllers/Api/V1/PosSyncApiController.php:86
* @route '/api/v1/pos/sync/status/{idempotency_key}'
*/
export const status = (args: { idempotency_key: string | number } | [idempotency_key: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: status.url(args, options),
    method: 'get',
})

status.definition = {
    methods: ["get","head"],
    url: '/api/v1/pos/sync/status/{idempotency_key}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\PosSyncApiController::status
* @see app/Http/Controllers/Api/V1/PosSyncApiController.php:86
* @route '/api/v1/pos/sync/status/{idempotency_key}'
*/
status.url = (args: { idempotency_key: string | number } | [idempotency_key: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { idempotency_key: args }
    }

    if (Array.isArray(args)) {
        args = {
            idempotency_key: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        idempotency_key: args.idempotency_key,
    }

    return status.definition.url
            .replace('{idempotency_key}', parsedArgs.idempotency_key.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\PosSyncApiController::status
* @see app/Http/Controllers/Api/V1/PosSyncApiController.php:86
* @route '/api/v1/pos/sync/status/{idempotency_key}'
*/
status.get = (args: { idempotency_key: string | number } | [idempotency_key: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: status.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\PosSyncApiController::status
* @see app/Http/Controllers/Api/V1/PosSyncApiController.php:86
* @route '/api/v1/pos/sync/status/{idempotency_key}'
*/
status.head = (args: { idempotency_key: string | number } | [idempotency_key: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: status.url(args, options),
    method: 'head',
})

const PosSyncApiController = { catalog, enqueue, process, processBatch, status }

export default PosSyncApiController