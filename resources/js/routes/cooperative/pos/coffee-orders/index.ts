import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\CoffeeOrderController::index
* @see app/Http/Controllers/Cooperative/CoffeeOrderController.php:16
* @route '/cooperative/pos/coffee-orders'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cooperative/pos/coffee-orders',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\CoffeeOrderController::index
* @see app/Http/Controllers/Cooperative/CoffeeOrderController.php:16
* @route '/cooperative/pos/coffee-orders'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CoffeeOrderController::index
* @see app/Http/Controllers/Cooperative/CoffeeOrderController.php:16
* @route '/cooperative/pos/coffee-orders'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CoffeeOrderController::index
* @see app/Http/Controllers/Cooperative/CoffeeOrderController.php:16
* @route '/cooperative/pos/coffee-orders'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\CoffeeOrderController::updateStatus
* @see app/Http/Controllers/Cooperative/CoffeeOrderController.php:56
* @route '/cooperative/pos/coffee-orders/{coffeeOrder}/status'
*/
export const updateStatus = (args: { coffeeOrder: string | number | { id: string | number } } | [coffeeOrder: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateStatus.url(args, options),
    method: 'put',
})

updateStatus.definition = {
    methods: ["put"],
    url: '/cooperative/pos/coffee-orders/{coffeeOrder}/status',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Cooperative\CoffeeOrderController::updateStatus
* @see app/Http/Controllers/Cooperative/CoffeeOrderController.php:56
* @route '/cooperative/pos/coffee-orders/{coffeeOrder}/status'
*/
updateStatus.url = (args: { coffeeOrder: string | number | { id: string | number } } | [coffeeOrder: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return updateStatus.definition.url
            .replace('{coffeeOrder}', parsedArgs.coffeeOrder.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CoffeeOrderController::updateStatus
* @see app/Http/Controllers/Cooperative/CoffeeOrderController.php:56
* @route '/cooperative/pos/coffee-orders/{coffeeOrder}/status'
*/
updateStatus.put = (args: { coffeeOrder: string | number | { id: string | number } } | [coffeeOrder: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateStatus.url(args, options),
    method: 'put',
})

const coffeeOrders = {
    index: Object.assign(index, index),
    updateStatus: Object.assign(updateStatus, updateStatus),
}

export default coffeeOrders