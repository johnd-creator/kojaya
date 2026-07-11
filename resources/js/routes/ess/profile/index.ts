import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\EssPortalController::update
* @see app/Http/Controllers/EssPortalController.php:63
* @route '/ess/profile'
*/
export const update = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/ess/profile',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\EssPortalController::update
* @see app/Http/Controllers/EssPortalController.php:63
* @route '/ess/profile'
*/
update.url = (options?: RouteQueryOptions) => {
    return update.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EssPortalController::update
* @see app/Http/Controllers/EssPortalController.php:63
* @route '/ess/profile'
*/
update.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(options),
    method: 'put',
})

const profile = {
    update: Object.assign(update, update),
}

export default profile