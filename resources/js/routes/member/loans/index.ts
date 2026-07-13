import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\MemberPortalController::store
* @see app/Http/Controllers/MemberPortalController.php:428
* @route '/member/loans'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/member/loans',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MemberPortalController::store
* @see app/Http/Controllers/MemberPortalController.php:428
* @route '/member/loans'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::store
* @see app/Http/Controllers/MemberPortalController.php:428
* @route '/member/loans'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

const loans = {
    store: Object.assign(store, store),
}

export default loans