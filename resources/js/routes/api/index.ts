import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\DashboardController::organizations
* @see app/Http/Controllers/DashboardController.php:68
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
* @see app/Http/Controllers/DashboardController.php:68
* @route '/api/organizations'
*/
organizations.url = (options?: RouteQueryOptions) => {
    return organizations.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DashboardController::organizations
* @see app/Http/Controllers/DashboardController.php:68
* @route '/api/organizations'
*/
organizations.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: organizations.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DashboardController::organizations
* @see app/Http/Controllers/DashboardController.php:68
* @route '/api/organizations'
*/
organizations.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: organizations.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DashboardController::organizations
* @see app/Http/Controllers/DashboardController.php:68
* @route '/api/organizations'
*/
const organizationsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: organizations.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DashboardController::organizations
* @see app/Http/Controllers/DashboardController.php:68
* @route '/api/organizations'
*/
organizationsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: organizations.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DashboardController::organizations
* @see app/Http/Controllers/DashboardController.php:68
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

const api = {
    organizations: Object.assign(organizations, organizations),
}

export default api