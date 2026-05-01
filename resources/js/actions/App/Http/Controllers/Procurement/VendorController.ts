import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Procurement\VendorController::index
* @see app/Http/Controllers/Procurement/VendorController.php:11
* @route '/procurement/vendors'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/procurement/vendors',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Procurement\VendorController::index
* @see app/Http/Controllers/Procurement/VendorController.php:11
* @route '/procurement/vendors'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Procurement\VendorController::index
* @see app/Http/Controllers/Procurement/VendorController.php:11
* @route '/procurement/vendors'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Procurement\VendorController::index
* @see app/Http/Controllers/Procurement/VendorController.php:11
* @route '/procurement/vendors'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Procurement\VendorController::index
* @see app/Http/Controllers/Procurement/VendorController.php:11
* @route '/procurement/vendors'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Procurement\VendorController::index
* @see app/Http/Controllers/Procurement/VendorController.php:11
* @route '/procurement/vendors'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Procurement\VendorController::index
* @see app/Http/Controllers/Procurement/VendorController.php:11
* @route '/procurement/vendors'
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

const VendorController = { index }

export default VendorController