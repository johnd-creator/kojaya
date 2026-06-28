import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\MemberCoffeeOrderController::index
* @see app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php:17
* @route '/api/v1/member/coffee/menu'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/coffee/menu',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberCoffeeOrderController::index
* @see app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php:17
* @route '/api/v1/member/coffee/menu'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberCoffeeOrderController::index
* @see app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php:17
* @route '/api/v1/member/coffee/menu'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberCoffeeOrderController::index
* @see app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php:17
* @route '/api/v1/member/coffee/menu'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberCoffeeOrderController::index
* @see app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php:17
* @route '/api/v1/member/coffee/menu'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberCoffeeOrderController::index
* @see app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php:17
* @route '/api/v1/member/coffee/menu'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberCoffeeOrderController::index
* @see app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php:17
* @route '/api/v1/member/coffee/menu'
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
* @see \App\Http\Controllers\Api\V1\MemberCoffeeOrderController::store
* @see app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php:41
* @route '/api/v1/member/coffee/orders'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/api/v1/member/coffee/orders',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberCoffeeOrderController::store
* @see app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php:41
* @route '/api/v1/member/coffee/orders'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberCoffeeOrderController::store
* @see app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php:41
* @route '/api/v1/member/coffee/orders'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberCoffeeOrderController::store
* @see app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php:41
* @route '/api/v1/member/coffee/orders'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberCoffeeOrderController::store
* @see app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php:41
* @route '/api/v1/member/coffee/orders'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\Api\V1\MemberCoffeeOrderController::show
* @see app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php:98
* @route '/api/v1/member/coffee/orders/{coffeeOrder}'
*/
export const show = (args: { coffeeOrder: number | { id: number } } | [coffeeOrder: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/coffee/orders/{coffeeOrder}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberCoffeeOrderController::show
* @see app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php:98
* @route '/api/v1/member/coffee/orders/{coffeeOrder}'
*/
show.url = (args: { coffeeOrder: number | { id: number } } | [coffeeOrder: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { coffeeOrder: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { coffeeOrder: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            coffeeOrder: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        coffeeOrder: typeof args.coffeeOrder === 'object'
        ? args.coffeeOrder.id
        : args.coffeeOrder,
    }

    return show.definition.url
            .replace('{coffeeOrder}', parsedArgs.coffeeOrder.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberCoffeeOrderController::show
* @see app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php:98
* @route '/api/v1/member/coffee/orders/{coffeeOrder}'
*/
show.get = (args: { coffeeOrder: number | { id: number } } | [coffeeOrder: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberCoffeeOrderController::show
* @see app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php:98
* @route '/api/v1/member/coffee/orders/{coffeeOrder}'
*/
show.head = (args: { coffeeOrder: number | { id: number } } | [coffeeOrder: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberCoffeeOrderController::show
* @see app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php:98
* @route '/api/v1/member/coffee/orders/{coffeeOrder}'
*/
const showForm = (args: { coffeeOrder: number | { id: number } } | [coffeeOrder: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberCoffeeOrderController::show
* @see app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php:98
* @route '/api/v1/member/coffee/orders/{coffeeOrder}'
*/
showForm.get = (args: { coffeeOrder: number | { id: number } } | [coffeeOrder: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberCoffeeOrderController::show
* @see app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php:98
* @route '/api/v1/member/coffee/orders/{coffeeOrder}'
*/
showForm.head = (args: { coffeeOrder: number | { id: number } } | [coffeeOrder: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const MemberCoffeeOrderController = { index, store, show }

export default MemberCoffeeOrderController