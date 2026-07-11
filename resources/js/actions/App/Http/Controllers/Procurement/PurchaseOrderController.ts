import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Procurement\PurchaseOrderController::index
* @see app/Http/Controllers/Procurement/PurchaseOrderController.php:18
* @route '/procurement/purchase-orders'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/procurement/purchase-orders',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Procurement\PurchaseOrderController::index
* @see app/Http/Controllers/Procurement/PurchaseOrderController.php:18
* @route '/procurement/purchase-orders'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Procurement\PurchaseOrderController::index
* @see app/Http/Controllers/Procurement/PurchaseOrderController.php:18
* @route '/procurement/purchase-orders'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseOrderController::index
* @see app/Http/Controllers/Procurement/PurchaseOrderController.php:18
* @route '/procurement/purchase-orders'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseOrderController::createFromPr
* @see app/Http/Controllers/Procurement/PurchaseOrderController.php:73
* @route '/procurement/purchase-orders/from-pr/{purchaseRequest}'
*/
export const createFromPr = (args: { purchaseRequest: string | { id: string } } | [purchaseRequest: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: createFromPr.url(args, options),
    method: 'post',
})

createFromPr.definition = {
    methods: ["post"],
    url: '/procurement/purchase-orders/from-pr/{purchaseRequest}',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Procurement\PurchaseOrderController::createFromPr
* @see app/Http/Controllers/Procurement/PurchaseOrderController.php:73
* @route '/procurement/purchase-orders/from-pr/{purchaseRequest}'
*/
createFromPr.url = (args: { purchaseRequest: string | { id: string } } | [purchaseRequest: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { purchaseRequest: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { purchaseRequest: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            purchaseRequest: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        purchaseRequest: typeof args.purchaseRequest === 'object'
        ? args.purchaseRequest.id
        : args.purchaseRequest,
    }

    return createFromPr.definition.url
            .replace('{purchaseRequest}', parsedArgs.purchaseRequest.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Procurement\PurchaseOrderController::createFromPr
* @see app/Http/Controllers/Procurement/PurchaseOrderController.php:73
* @route '/procurement/purchase-orders/from-pr/{purchaseRequest}'
*/
createFromPr.post = (args: { purchaseRequest: string | { id: string } } | [purchaseRequest: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: createFromPr.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseOrderController::show
* @see app/Http/Controllers/Procurement/PurchaseOrderController.php:44
* @route '/procurement/purchase-orders/{purchaseOrder}'
*/
export const show = (args: { purchaseOrder: string | { id: string } } | [purchaseOrder: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/procurement/purchase-orders/{purchaseOrder}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Procurement\PurchaseOrderController::show
* @see app/Http/Controllers/Procurement/PurchaseOrderController.php:44
* @route '/procurement/purchase-orders/{purchaseOrder}'
*/
show.url = (args: { purchaseOrder: string | { id: string } } | [purchaseOrder: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { purchaseOrder: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { purchaseOrder: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            purchaseOrder: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        purchaseOrder: typeof args.purchaseOrder === 'object'
        ? args.purchaseOrder.id
        : args.purchaseOrder,
    }

    return show.definition.url
            .replace('{purchaseOrder}', parsedArgs.purchaseOrder.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Procurement\PurchaseOrderController::show
* @see app/Http/Controllers/Procurement/PurchaseOrderController.php:44
* @route '/procurement/purchase-orders/{purchaseOrder}'
*/
show.get = (args: { purchaseOrder: string | { id: string } } | [purchaseOrder: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseOrderController::show
* @see app/Http/Controllers/Procurement/PurchaseOrderController.php:44
* @route '/procurement/purchase-orders/{purchaseOrder}'
*/
show.head = (args: { purchaseOrder: string | { id: string } } | [purchaseOrder: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

const PurchaseOrderController = { index, createFromPr, show }

export default PurchaseOrderController