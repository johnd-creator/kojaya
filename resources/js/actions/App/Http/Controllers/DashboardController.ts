import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\DashboardController::index
* @see app/Http/Controllers/DashboardController.php:20
* @route '/api/dashboard'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/dashboard',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DashboardController::index
* @see app/Http/Controllers/DashboardController.php:20
* @route '/api/dashboard'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DashboardController::index
* @see app/Http/Controllers/DashboardController.php:20
* @route '/api/dashboard'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DashboardController::index
* @see app/Http/Controllers/DashboardController.php:20
* @route '/api/dashboard'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DashboardController::index
* @see app/Http/Controllers/DashboardController.php:20
* @route '/api/dashboard'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DashboardController::index
* @see app/Http/Controllers/DashboardController.php:20
* @route '/api/dashboard'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DashboardController::index
* @see app/Http/Controllers/DashboardController.php:20
* @route '/api/dashboard'
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
* @see \App\Http\Controllers\DashboardController::organizations
* @see app/Http/Controllers/DashboardController.php:59
* @route '/api/organizations'
*/
export const organizations = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: organizations.url(options),
    method: 'get',
})

organizations.definition = {
    methods: ["get","head"],
    url: '/api/organizations',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DashboardController::organizations
* @see app/Http/Controllers/DashboardController.php:59
* @route '/api/organizations'
*/
organizations.url = (options?: RouteQueryOptions) => {
    return organizations.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DashboardController::organizations
* @see app/Http/Controllers/DashboardController.php:59
* @route '/api/organizations'
*/
organizations.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: organizations.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DashboardController::organizations
* @see app/Http/Controllers/DashboardController.php:59
* @route '/api/organizations'
*/
organizations.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: organizations.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DashboardController::organizations
* @see app/Http/Controllers/DashboardController.php:59
* @route '/api/organizations'
*/
const organizationsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: organizations.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DashboardController::organizations
* @see app/Http/Controllers/DashboardController.php:59
* @route '/api/organizations'
*/
organizationsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: organizations.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DashboardController::organizations
* @see app/Http/Controllers/DashboardController.php:59
* @route '/api/organizations'
*/
organizationsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: organizations.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

organizations.form = organizationsForm

const DashboardController = { index, organizations }

export default DashboardController