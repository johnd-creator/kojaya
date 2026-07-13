import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::index
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:22
* @route '/procurement/purchase-requests'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/procurement/purchase-requests',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::index
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:22
* @route '/procurement/purchase-requests'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::index
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:22
* @route '/procurement/purchase-requests'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::index
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:22
* @route '/procurement/purchase-requests'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::create
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:52
* @route '/procurement/purchase-requests/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/procurement/purchase-requests/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::create
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:52
* @route '/procurement/purchase-requests/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::create
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:52
* @route '/procurement/purchase-requests/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::create
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:52
* @route '/procurement/purchase-requests/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::store
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:85
* @route '/procurement/purchase-requests'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/procurement/purchase-requests',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::store
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:85
* @route '/procurement/purchase-requests'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::store
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:85
* @route '/procurement/purchase-requests'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::show
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:122
* @route '/procurement/purchase-requests/{purchaseRequest}'
*/
export const show = (args: { purchaseRequest: string | number | { id: string | number } } | [purchaseRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/procurement/purchase-requests/{purchaseRequest}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::show
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:122
* @route '/procurement/purchase-requests/{purchaseRequest}'
*/
show.url = (args: { purchaseRequest: string | number | { id: string | number } } | [purchaseRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return show.definition.url
            .replace('{purchaseRequest}', parsedArgs.purchaseRequest.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::show
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:122
* @route '/procurement/purchase-requests/{purchaseRequest}'
*/
show.get = (args: { purchaseRequest: string | number | { id: string | number } } | [purchaseRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::show
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:122
* @route '/procurement/purchase-requests/{purchaseRequest}'
*/
show.head = (args: { purchaseRequest: string | number | { id: string | number } } | [purchaseRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::submit
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:166
* @route '/procurement/purchase-requests/{purchaseRequest}/submit'
*/
export const submit = (args: { purchaseRequest: string | number | { id: string | number } } | [purchaseRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submit.url(args, options),
    method: 'post',
})

submit.definition = {
    methods: ["post"],
    url: '/procurement/purchase-requests/{purchaseRequest}/submit',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::submit
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:166
* @route '/procurement/purchase-requests/{purchaseRequest}/submit'
*/
submit.url = (args: { purchaseRequest: string | number | { id: string | number } } | [purchaseRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return submit.definition.url
            .replace('{purchaseRequest}', parsedArgs.purchaseRequest.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::submit
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:166
* @route '/procurement/purchase-requests/{purchaseRequest}/submit'
*/
submit.post = (args: { purchaseRequest: string | number | { id: string | number } } | [purchaseRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submit.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::approve
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:183
* @route '/procurement/purchase-requests/{purchaseRequest}/approve'
*/
export const approve = (args: { purchaseRequest: string | number | { id: string | number } } | [purchaseRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

approve.definition = {
    methods: ["post"],
    url: '/procurement/purchase-requests/{purchaseRequest}/approve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::approve
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:183
* @route '/procurement/purchase-requests/{purchaseRequest}/approve'
*/
approve.url = (args: { purchaseRequest: string | number | { id: string | number } } | [purchaseRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return approve.definition.url
            .replace('{purchaseRequest}', parsedArgs.purchaseRequest.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::approve
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:183
* @route '/procurement/purchase-requests/{purchaseRequest}/approve'
*/
approve.post = (args: { purchaseRequest: string | number | { id: string | number } } | [purchaseRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::reject
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:197
* @route '/procurement/purchase-requests/{purchaseRequest}/reject'
*/
export const reject = (args: { purchaseRequest: string | number | { id: string | number } } | [purchaseRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
})

reject.definition = {
    methods: ["post"],
    url: '/procurement/purchase-requests/{purchaseRequest}/reject',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::reject
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:197
* @route '/procurement/purchase-requests/{purchaseRequest}/reject'
*/
reject.url = (args: { purchaseRequest: string | number | { id: string | number } } | [purchaseRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return reject.definition.url
            .replace('{purchaseRequest}', parsedArgs.purchaseRequest.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::reject
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:197
* @route '/procurement/purchase-requests/{purchaseRequest}/reject'
*/
reject.post = (args: { purchaseRequest: string | number | { id: string | number } } | [purchaseRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
})

const PurchaseRequestController = { index, create, store, show, submit, approve, reject }

export default PurchaseRequestController