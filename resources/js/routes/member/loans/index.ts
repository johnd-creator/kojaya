import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\MemberPortalController::store
* @see app/Http/Controllers/MemberPortalController.php:265
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
* @see app/Http/Controllers/MemberPortalController.php:265
* @route '/member/loans'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::store
* @see app/Http/Controllers/MemberPortalController.php:265
* @route '/member/loans'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MemberPortalController::store
* @see app/Http/Controllers/MemberPortalController.php:265
* @route '/member/loans'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MemberPortalController::store
* @see app/Http/Controllers/MemberPortalController.php:265
* @route '/member/loans'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

const loans = {
    store: Object.assign(store, store),
}

export default loans