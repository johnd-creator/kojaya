import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\EfakturController::createBatch
* @see app/Http/Controllers/EfakturController.php:13
* @route '/invoices/efaktur/batch'
*/
export const createBatch = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: createBatch.url(options),
    method: 'post',
})

createBatch.definition = {
    methods: ["post"],
    url: '/invoices/efaktur/batch',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EfakturController::createBatch
* @see app/Http/Controllers/EfakturController.php:13
* @route '/invoices/efaktur/batch'
*/
createBatch.url = (options?: RouteQueryOptions) => {
    return createBatch.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EfakturController::createBatch
* @see app/Http/Controllers/EfakturController.php:13
* @route '/invoices/efaktur/batch'
*/
createBatch.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: createBatch.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EfakturController::downloadCsv
* @see app/Http/Controllers/EfakturController.php:40
* @route '/invoices/efaktur/batches/{batch}/csv'
*/
export const downloadCsv = (args: { batch: string | { id: string } } | [batch: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: downloadCsv.url(args, options),
    method: 'get',
})

downloadCsv.definition = {
    methods: ["get","head"],
    url: '/invoices/efaktur/batches/{batch}/csv',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EfakturController::downloadCsv
* @see app/Http/Controllers/EfakturController.php:40
* @route '/invoices/efaktur/batches/{batch}/csv'
*/
downloadCsv.url = (args: { batch: string | { id: string } } | [batch: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { batch: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { batch: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            batch: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        batch: typeof args.batch === 'object'
        ? args.batch.id
        : args.batch,
    }

    return downloadCsv.definition.url
            .replace('{batch}', parsedArgs.batch.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EfakturController::downloadCsv
* @see app/Http/Controllers/EfakturController.php:40
* @route '/invoices/efaktur/batches/{batch}/csv'
*/
downloadCsv.get = (args: { batch: string | { id: string } } | [batch: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: downloadCsv.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EfakturController::downloadCsv
* @see app/Http/Controllers/EfakturController.php:40
* @route '/invoices/efaktur/batches/{batch}/csv'
*/
downloadCsv.head = (args: { batch: string | { id: string } } | [batch: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: downloadCsv.url(args, options),
    method: 'head',
})

const EfakturController = { createBatch, downloadCsv }

export default EfakturController