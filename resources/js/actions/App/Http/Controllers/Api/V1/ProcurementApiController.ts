import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\ProcurementApiController::vendorPerformance
* @see app/Http/Controllers/Api/V1/ProcurementApiController.php:12
* @route '/api/v1/procurement/vendors/{vendor}/performance'
*/
export const vendorPerformance = (args: { vendor: string | { id: string } } | [vendor: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: vendorPerformance.url(args, options),
    method: 'get',
})

vendorPerformance.definition = {
    methods: ["get","head"],
    url: '/api/v1/procurement/vendors/{vendor}/performance',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\ProcurementApiController::vendorPerformance
* @see app/Http/Controllers/Api/V1/ProcurementApiController.php:12
* @route '/api/v1/procurement/vendors/{vendor}/performance'
*/
vendorPerformance.url = (args: { vendor: string | { id: string } } | [vendor: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { vendor: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { vendor: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            vendor: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        vendor: typeof args.vendor === 'object'
        ? args.vendor.id
        : args.vendor,
    }

    return vendorPerformance.definition.url
            .replace('{vendor}', parsedArgs.vendor.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\ProcurementApiController::vendorPerformance
* @see app/Http/Controllers/Api/V1/ProcurementApiController.php:12
* @route '/api/v1/procurement/vendors/{vendor}/performance'
*/
vendorPerformance.get = (args: { vendor: string | { id: string } } | [vendor: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: vendorPerformance.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\ProcurementApiController::vendorPerformance
* @see app/Http/Controllers/Api/V1/ProcurementApiController.php:12
* @route '/api/v1/procurement/vendors/{vendor}/performance'
*/
vendorPerformance.head = (args: { vendor: string | { id: string } } | [vendor: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: vendorPerformance.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\ProcurementApiController::vendorPerformance
* @see app/Http/Controllers/Api/V1/ProcurementApiController.php:12
* @route '/api/v1/procurement/vendors/{vendor}/performance'
*/
const vendorPerformanceForm = (args: { vendor: string | { id: string } } | [vendor: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: vendorPerformance.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\ProcurementApiController::vendorPerformance
* @see app/Http/Controllers/Api/V1/ProcurementApiController.php:12
* @route '/api/v1/procurement/vendors/{vendor}/performance'
*/
vendorPerformanceForm.get = (args: { vendor: string | { id: string } } | [vendor: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: vendorPerformance.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\ProcurementApiController::vendorPerformance
* @see app/Http/Controllers/Api/V1/ProcurementApiController.php:12
* @route '/api/v1/procurement/vendors/{vendor}/performance'
*/
vendorPerformanceForm.head = (args: { vendor: string | { id: string } } | [vendor: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: vendorPerformance.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

vendorPerformance.form = vendorPerformanceForm

const ProcurementApiController = { vendorPerformance }

export default ProcurementApiController