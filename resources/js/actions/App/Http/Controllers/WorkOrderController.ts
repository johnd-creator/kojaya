import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\WorkOrderController::index
* @see app/Http/Controllers/WorkOrderController.php:12
* @route '/work-orders'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/work-orders',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WorkOrderController::index
* @see app/Http/Controllers/WorkOrderController.php:12
* @route '/work-orders'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WorkOrderController::index
* @see app/Http/Controllers/WorkOrderController.php:12
* @route '/work-orders'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WorkOrderController::index
* @see app/Http/Controllers/WorkOrderController.php:12
* @route '/work-orders'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\WorkOrderController::create
* @see app/Http/Controllers/WorkOrderController.php:34
* @route '/work-orders/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/work-orders/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WorkOrderController::create
* @see app/Http/Controllers/WorkOrderController.php:34
* @route '/work-orders/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WorkOrderController::create
* @see app/Http/Controllers/WorkOrderController.php:34
* @route '/work-orders/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WorkOrderController::create
* @see app/Http/Controllers/WorkOrderController.php:34
* @route '/work-orders/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\WorkOrderController::show
* @see app/Http/Controllers/WorkOrderController.php:47
* @route '/work-orders/{workOrder}'
*/
export const show = (args: { workOrder: string | { id: string } } | [workOrder: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/work-orders/{workOrder}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WorkOrderController::show
* @see app/Http/Controllers/WorkOrderController.php:47
* @route '/work-orders/{workOrder}'
*/
show.url = (args: { workOrder: string | { id: string } } | [workOrder: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { workOrder: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { workOrder: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            workOrder: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        workOrder: typeof args.workOrder === 'object'
        ? args.workOrder.id
        : args.workOrder,
    }

    return show.definition.url
            .replace('{workOrder}', parsedArgs.workOrder.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\WorkOrderController::show
* @see app/Http/Controllers/WorkOrderController.php:47
* @route '/work-orders/{workOrder}'
*/
show.get = (args: { workOrder: string | { id: string } } | [workOrder: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WorkOrderController::show
* @see app/Http/Controllers/WorkOrderController.php:47
* @route '/work-orders/{workOrder}'
*/
show.head = (args: { workOrder: string | { id: string } } | [workOrder: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\WorkOrderController::store
* @see app/Http/Controllers/WorkOrderController.php:62
* @route '/work-orders'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/work-orders',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\WorkOrderController::store
* @see app/Http/Controllers/WorkOrderController.php:62
* @route '/work-orders'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WorkOrderController::store
* @see app/Http/Controllers/WorkOrderController.php:62
* @route '/work-orders'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

const WorkOrderController = { index, create, show, store }

export default WorkOrderController