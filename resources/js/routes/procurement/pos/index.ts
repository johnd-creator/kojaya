import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
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
* @see \App\Http\Controllers\Procurement\PurchaseOrderController::index
* @see app/Http/Controllers/Procurement/PurchaseOrderController.php:18
* @route '/procurement/purchase-orders'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseOrderController::index
* @see app/Http/Controllers/Procurement/PurchaseOrderController.php:18
* @route '/procurement/purchase-orders'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseOrderController::index
* @see app/Http/Controllers/Procurement/PurchaseOrderController.php:18
* @route '/procurement/purchase-orders'
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
* @see \App\Http\Controllers\Procurement\PurchaseOrderController::fromPr
* @see app/Http/Controllers/Procurement/PurchaseOrderController.php:73
* @route '/procurement/purchase-orders/from-pr/{purchaseRequest}'
*/
export const fromPr = (args: { purchaseRequest: string | number | { id: string | number } } | [purchaseRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: fromPr.url(args, options),
    method: 'post',
})

fromPr.definition = {
    methods: ["post"],
    url: '/procurement/purchase-orders/from-pr/{purchaseRequest}',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Procurement\PurchaseOrderController::fromPr
* @see app/Http/Controllers/Procurement/PurchaseOrderController.php:73
* @route '/procurement/purchase-orders/from-pr/{purchaseRequest}'
*/
fromPr.url = (args: { purchaseRequest: string | number | { id: string | number } } | [purchaseRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return fromPr.definition.url
            .replace('{purchaseRequest}', parsedArgs.purchaseRequest.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Procurement\PurchaseOrderController::fromPr
* @see app/Http/Controllers/Procurement/PurchaseOrderController.php:73
* @route '/procurement/purchase-orders/from-pr/{purchaseRequest}'
*/
fromPr.post = (args: { purchaseRequest: string | number | { id: string | number } } | [purchaseRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: fromPr.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseOrderController::fromPr
* @see app/Http/Controllers/Procurement/PurchaseOrderController.php:73
* @route '/procurement/purchase-orders/from-pr/{purchaseRequest}'
*/
const fromPrForm = (args: { purchaseRequest: string | number | { id: string | number } } | [purchaseRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: fromPr.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseOrderController::fromPr
* @see app/Http/Controllers/Procurement/PurchaseOrderController.php:73
* @route '/procurement/purchase-orders/from-pr/{purchaseRequest}'
*/
fromPrForm.post = (args: { purchaseRequest: string | number | { id: string | number } } | [purchaseRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: fromPr.url(args, options),
    method: 'post',
})

fromPr.form = fromPrForm

/**
* @see \App\Http\Controllers\Procurement\PurchaseOrderController::show
* @see app/Http/Controllers/Procurement/PurchaseOrderController.php:44
* @route '/procurement/purchase-orders/{purchaseOrder}'
*/
export const show = (args: { purchaseOrder: string | number | { id: string | number } } | [purchaseOrder: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
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
show.url = (args: { purchaseOrder: string | number | { id: string | number } } | [purchaseOrder: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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
show.get = (args: { purchaseOrder: string | number | { id: string | number } } | [purchaseOrder: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseOrderController::show
* @see app/Http/Controllers/Procurement/PurchaseOrderController.php:44
* @route '/procurement/purchase-orders/{purchaseOrder}'
*/
show.head = (args: { purchaseOrder: string | number | { id: string | number } } | [purchaseOrder: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseOrderController::show
* @see app/Http/Controllers/Procurement/PurchaseOrderController.php:44
* @route '/procurement/purchase-orders/{purchaseOrder}'
*/
const showForm = (args: { purchaseOrder: string | number | { id: string | number } } | [purchaseOrder: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseOrderController::show
* @see app/Http/Controllers/Procurement/PurchaseOrderController.php:44
* @route '/procurement/purchase-orders/{purchaseOrder}'
*/
showForm.get = (args: { purchaseOrder: string | number | { id: string | number } } | [purchaseOrder: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseOrderController::show
* @see app/Http/Controllers/Procurement/PurchaseOrderController.php:44
* @route '/procurement/purchase-orders/{purchaseOrder}'
*/
showForm.head = (args: { purchaseOrder: string | number | { id: string | number } } | [purchaseOrder: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const pos = {
    index: Object.assign(index, index),
    fromPr: Object.assign(fromPr, fromPr),
    show: Object.assign(show, show),
}

export default pos