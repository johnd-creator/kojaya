import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
import api from './api'
/**
* @see \App\Http\Controllers\EfakturController::batchCreate
* @see app/Http/Controllers/EfakturController.php:13
* @route '/invoices/efaktur/batch'
*/
export const batchCreate = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: batchCreate.url(options),
    method: 'post',
})

batchCreate.definition = {
    methods: ["post"],
    url: '/invoices/efaktur/batch',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EfakturController::batchCreate
* @see app/Http/Controllers/EfakturController.php:13
* @route '/invoices/efaktur/batch'
*/
batchCreate.url = (options?: RouteQueryOptions) => {
    return batchCreate.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EfakturController::batchCreate
* @see app/Http/Controllers/EfakturController.php:13
* @route '/invoices/efaktur/batch'
*/
batchCreate.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: batchCreate.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EfakturController::batchCsv
* @see app/Http/Controllers/EfakturController.php:38
* @route '/invoices/efaktur/batches/{batch}/csv'
*/
export const batchCsv = (args: { batch: string | number | { id: string | number } } | [batch: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: batchCsv.url(args, options),
    method: 'get',
})

batchCsv.definition = {
    methods: ["get","head"],
    url: '/invoices/efaktur/batches/{batch}/csv',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EfakturController::batchCsv
* @see app/Http/Controllers/EfakturController.php:38
* @route '/invoices/efaktur/batches/{batch}/csv'
*/
batchCsv.url = (args: { batch: string | number | { id: string | number } } | [batch: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return batchCsv.definition.url
            .replace('{batch}', parsedArgs.batch.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EfakturController::batchCsv
* @see app/Http/Controllers/EfakturController.php:38
* @route '/invoices/efaktur/batches/{batch}/csv'
*/
batchCsv.get = (args: { batch: string | number | { id: string | number } } | [batch: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: batchCsv.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EfakturController::batchCsv
* @see app/Http/Controllers/EfakturController.php:38
* @route '/invoices/efaktur/batches/{batch}/csv'
*/
batchCsv.head = (args: { batch: string | number | { id: string | number } } | [batch: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: batchCsv.url(args, options),
    method: 'head',
})

const efaktur = {
    batchCreate: Object.assign(batchCreate, batchCreate),
    batchCsv: Object.assign(batchCsv, batchCsv),
    api: Object.assign(api, api),
}

export default efaktur