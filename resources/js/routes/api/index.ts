import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
import v1 from './v1'
/**
* @see \App\Http\Controllers\DashboardController::organizations
* @see app/Http/Controllers/DashboardController.php:82
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
* @see app/Http/Controllers/DashboardController.php:82
* @route '/api/organizations'
*/
organizations.url = (options?: RouteQueryOptions) => {
    return organizations.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DashboardController::organizations
* @see app/Http/Controllers/DashboardController.php:82
* @route '/api/organizations'
*/
organizations.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: organizations.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DashboardController::organizations
* @see app/Http/Controllers/DashboardController.php:82
* @route '/api/organizations'
*/
organizations.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: organizations.url(options),
    method: 'head',
})

const api = {
    v1: Object.assign(v1, v1),
    organizations: Object.assign(organizations, organizations),
}

export default api