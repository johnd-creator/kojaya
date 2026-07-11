import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\MemberPortalController::update
* @see app/Http/Controllers/MemberPortalController.php:549
* @route '/member/profile'
*/
export const update = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/member/profile',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\MemberPortalController::update
* @see app/Http/Controllers/MemberPortalController.php:549
* @route '/member/profile'
*/
update.url = (options?: RouteQueryOptions) => {
    return update.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::update
* @see app/Http/Controllers/MemberPortalController.php:549
* @route '/member/profile'
*/
update.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(options),
    method: 'put',
})

const profile = {
    update: Object.assign(update, update),
}

export default profile