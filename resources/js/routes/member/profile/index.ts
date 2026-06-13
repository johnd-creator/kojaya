import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\MemberPortalController::update
* @see app/Http/Controllers/MemberPortalController.php:501
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
* @see app/Http/Controllers/MemberPortalController.php:501
* @route '/member/profile'
*/
update.url = (options?: RouteQueryOptions) => {
    return update.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::update
* @see app/Http/Controllers/MemberPortalController.php:501
* @route '/member/profile'
*/
update.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\MemberPortalController::update
* @see app/Http/Controllers/MemberPortalController.php:501
* @route '/member/profile'
*/
const updateForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MemberPortalController::update
* @see app/Http/Controllers/MemberPortalController.php:501
* @route '/member/profile'
*/
updateForm.put = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

const profile = {
    update: Object.assign(update, update),
}

export default profile