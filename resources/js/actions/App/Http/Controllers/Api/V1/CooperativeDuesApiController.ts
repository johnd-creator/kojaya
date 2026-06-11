import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\CooperativeDuesApiController::invoices
* @see app/Http/Controllers/Api/V1/CooperativeDuesApiController.php:14
* @route '/api/v1/dues/invoices'
*/
export const invoices = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: invoices.url(options),
    method: 'get',
})

invoices.definition = {
    methods: ["get","head"],
    url: '/api/v1/dues/invoices',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\CooperativeDuesApiController::invoices
* @see app/Http/Controllers/Api/V1/CooperativeDuesApiController.php:14
* @route '/api/v1/dues/invoices'
*/
invoices.url = (options?: RouteQueryOptions) => {
    return invoices.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CooperativeDuesApiController::invoices
* @see app/Http/Controllers/Api/V1/CooperativeDuesApiController.php:14
* @route '/api/v1/dues/invoices'
*/
invoices.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: invoices.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeDuesApiController::invoices
* @see app/Http/Controllers/Api/V1/CooperativeDuesApiController.php:14
* @route '/api/v1/dues/invoices'
*/
invoices.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: invoices.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeDuesApiController::invoices
* @see app/Http/Controllers/Api/V1/CooperativeDuesApiController.php:14
* @route '/api/v1/dues/invoices'
*/
const invoicesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: invoices.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeDuesApiController::invoices
* @see app/Http/Controllers/Api/V1/CooperativeDuesApiController.php:14
* @route '/api/v1/dues/invoices'
*/
invoicesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: invoices.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeDuesApiController::invoices
* @see app/Http/Controllers/Api/V1/CooperativeDuesApiController.php:14
* @route '/api/v1/dues/invoices'
*/
invoicesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: invoices.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

invoices.form = invoicesForm

/**
* @see \App\Http\Controllers\Api\V1\CooperativeDuesApiController::generate
* @see app/Http/Controllers/Api/V1/CooperativeDuesApiController.php:45
* @route '/api/v1/dues/generate'
*/
export const generate = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: generate.url(options),
    method: 'post',
})

generate.definition = {
    methods: ["post"],
    url: '/api/v1/dues/generate',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\CooperativeDuesApiController::generate
* @see app/Http/Controllers/Api/V1/CooperativeDuesApiController.php:45
* @route '/api/v1/dues/generate'
*/
generate.url = (options?: RouteQueryOptions) => {
    return generate.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CooperativeDuesApiController::generate
* @see app/Http/Controllers/Api/V1/CooperativeDuesApiController.php:45
* @route '/api/v1/dues/generate'
*/
generate.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: generate.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeDuesApiController::generate
* @see app/Http/Controllers/Api/V1/CooperativeDuesApiController.php:45
* @route '/api/v1/dues/generate'
*/
const generateForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: generate.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeDuesApiController::generate
* @see app/Http/Controllers/Api/V1/CooperativeDuesApiController.php:45
* @route '/api/v1/dues/generate'
*/
generateForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: generate.url(options),
    method: 'post',
})

generate.form = generateForm

const CooperativeDuesApiController = { invoices, generate }

export default CooperativeDuesApiController