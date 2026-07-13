import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\MemberStoreController::catalog
* @see app/Http/Controllers/Api/V1/MemberStoreController.php:18
* @route '/api/v1/member/store/catalog'
*/
export const catalog = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: catalog.url(options),
    method: 'get',
})

catalog.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/store/catalog',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberStoreController::catalog
* @see app/Http/Controllers/Api/V1/MemberStoreController.php:18
* @route '/api/v1/member/store/catalog'
*/
catalog.url = (options?: RouteQueryOptions) => {
    return catalog.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberStoreController::catalog
* @see app/Http/Controllers/Api/V1/MemberStoreController.php:18
* @route '/api/v1/member/store/catalog'
*/
catalog.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: catalog.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberStoreController::catalog
* @see app/Http/Controllers/Api/V1/MemberStoreController.php:18
* @route '/api/v1/member/store/catalog'
*/
catalog.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: catalog.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberStoreController::catalog
* @see app/Http/Controllers/Api/V1/MemberStoreController.php:18
* @route '/api/v1/member/store/catalog'
*/
const catalogForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: catalog.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberStoreController::catalog
* @see app/Http/Controllers/Api/V1/MemberStoreController.php:18
* @route '/api/v1/member/store/catalog'
*/
catalogForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: catalog.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberStoreController::catalog
* @see app/Http/Controllers/Api/V1/MemberStoreController.php:18
* @route '/api/v1/member/store/catalog'
*/
catalogForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: catalog.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

catalog.form = catalogForm

/**
* @see \App\Http\Controllers\Api\V1\MemberStoreController::store
* @see app/Http/Controllers/Api/V1/MemberStoreController.php:56
* @route '/api/v1/member/store/orders'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/api/v1/member/store/orders',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberStoreController::store
* @see app/Http/Controllers/Api/V1/MemberStoreController.php:56
* @route '/api/v1/member/store/orders'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberStoreController::store
* @see app/Http/Controllers/Api/V1/MemberStoreController.php:56
* @route '/api/v1/member/store/orders'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberStoreController::store
* @see app/Http/Controllers/Api/V1/MemberStoreController.php:56
* @route '/api/v1/member/store/orders'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberStoreController::store
* @see app/Http/Controllers/Api/V1/MemberStoreController.php:56
* @route '/api/v1/member/store/orders'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\Api\V1\MemberStoreController::showIntent
* @see app/Http/Controllers/Api/V1/MemberStoreController.php:108
* @route '/api/v1/member/payment-intents/{intent}'
*/
export const showIntent = (args: { intent: string | number | { id: string | number } } | [intent: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showIntent.url(args, options),
    method: 'get',
})

showIntent.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/payment-intents/{intent}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberStoreController::showIntent
* @see app/Http/Controllers/Api/V1/MemberStoreController.php:108
* @route '/api/v1/member/payment-intents/{intent}'
*/
showIntent.url = (args: { intent: string | number | { id: string | number } } | [intent: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { intent: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { intent: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            intent: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        intent: typeof args.intent === 'object'
        ? args.intent.id
        : args.intent,
    }

    return showIntent.definition.url
            .replace('{intent}', parsedArgs.intent.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberStoreController::showIntent
* @see app/Http/Controllers/Api/V1/MemberStoreController.php:108
* @route '/api/v1/member/payment-intents/{intent}'
*/
showIntent.get = (args: { intent: string | number | { id: string | number } } | [intent: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showIntent.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberStoreController::showIntent
* @see app/Http/Controllers/Api/V1/MemberStoreController.php:108
* @route '/api/v1/member/payment-intents/{intent}'
*/
showIntent.head = (args: { intent: string | number | { id: string | number } } | [intent: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showIntent.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberStoreController::showIntent
* @see app/Http/Controllers/Api/V1/MemberStoreController.php:108
* @route '/api/v1/member/payment-intents/{intent}'
*/
const showIntentForm = (args: { intent: string | number | { id: string | number } } | [intent: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showIntent.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberStoreController::showIntent
* @see app/Http/Controllers/Api/V1/MemberStoreController.php:108
* @route '/api/v1/member/payment-intents/{intent}'
*/
showIntentForm.get = (args: { intent: string | number | { id: string | number } } | [intent: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showIntent.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberStoreController::showIntent
* @see app/Http/Controllers/Api/V1/MemberStoreController.php:108
* @route '/api/v1/member/payment-intents/{intent}'
*/
showIntentForm.head = (args: { intent: string | number | { id: string | number } } | [intent: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showIntent.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

showIntent.form = showIntentForm

const MemberStoreController = { catalog, store, showIntent }

export default MemberStoreController