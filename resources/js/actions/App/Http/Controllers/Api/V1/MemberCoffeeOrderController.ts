import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\MemberCoffeeOrderController::index
* @see app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php:19
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
* @see app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php:19
* @route '/api/v1/member/coffee/menu'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberCoffeeOrderController::index
* @see app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php:19
* @route '/api/v1/member/coffee/menu'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberCoffeeOrderController::index
* @see app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php:19
* @route '/api/v1/member/coffee/menu'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberCoffeeOrderController::store
* @see app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php:43
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
* @see app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php:43
* @route '/api/v1/member/coffee/orders'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberCoffeeOrderController::store
* @see app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php:43
* @route '/api/v1/member/coffee/orders'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberCoffeeOrderController::show
* @see app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php:87
* @route '/api/v1/member/coffee/orders/{coffeeOrder}'
*/
export const show = (args: { coffeeOrder: string | number | { id: string | number } } | [coffeeOrder: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/coffee/orders/{coffeeOrder}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberCoffeeOrderController::show
* @see app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php:87
* @route '/api/v1/member/coffee/orders/{coffeeOrder}'
*/
show.url = (args: { coffeeOrder: string | number | { id: string | number } } | [coffeeOrder: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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
* @see app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php:87
* @route '/api/v1/member/coffee/orders/{coffeeOrder}'
*/
show.get = (args: { coffeeOrder: string | number | { id: string | number } } | [coffeeOrder: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberCoffeeOrderController::show
* @see app/Http/Controllers/Api/V1/MemberCoffeeOrderController.php:87
* @route '/api/v1/member/coffee/orders/{coffeeOrder}'
*/
show.head = (args: { coffeeOrder: string | number | { id: string | number } } | [coffeeOrder: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

const MemberCoffeeOrderController = { index, store, show }

export default MemberCoffeeOrderController