import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::index
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:23
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
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:23
* @route '/procurement/purchase-requests'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::index
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:23
* @route '/procurement/purchase-requests'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::index
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:23
* @route '/procurement/purchase-requests'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::index
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:23
* @route '/procurement/purchase-requests'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::index
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:23
* @route '/procurement/purchase-requests'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::index
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:23
* @route '/procurement/purchase-requests'
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
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::create
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:53
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
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:53
* @route '/procurement/purchase-requests/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::create
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:53
* @route '/procurement/purchase-requests/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::create
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:53
* @route '/procurement/purchase-requests/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::create
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:53
* @route '/procurement/purchase-requests/create'
*/
const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::create
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:53
* @route '/procurement/purchase-requests/create'
*/
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::create
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:53
* @route '/procurement/purchase-requests/create'
*/
createForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

create.form = createForm

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::store
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:86
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
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:86
* @route '/procurement/purchase-requests'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::store
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:86
* @route '/procurement/purchase-requests'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::store
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:86
* @route '/procurement/purchase-requests'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::store
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:86
* @route '/procurement/purchase-requests'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::show
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:123
* @route '/procurement/purchase-requests/{purchaseRequest}'
*/
export const show = (args: { purchaseRequest: string | { id: string } } | [purchaseRequest: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/procurement/purchase-requests/{purchaseRequest}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::show
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:123
* @route '/procurement/purchase-requests/{purchaseRequest}'
*/
show.url = (args: { purchaseRequest: string | { id: string } } | [purchaseRequest: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
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
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:123
* @route '/procurement/purchase-requests/{purchaseRequest}'
*/
show.get = (args: { purchaseRequest: string | { id: string } } | [purchaseRequest: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::show
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:123
* @route '/procurement/purchase-requests/{purchaseRequest}'
*/
show.head = (args: { purchaseRequest: string | { id: string } } | [purchaseRequest: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::show
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:123
* @route '/procurement/purchase-requests/{purchaseRequest}'
*/
const showForm = (args: { purchaseRequest: string | { id: string } } | [purchaseRequest: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::show
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:123
* @route '/procurement/purchase-requests/{purchaseRequest}'
*/
showForm.get = (args: { purchaseRequest: string | { id: string } } | [purchaseRequest: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::show
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:123
* @route '/procurement/purchase-requests/{purchaseRequest}'
*/
showForm.head = (args: { purchaseRequest: string | { id: string } } | [purchaseRequest: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::submit
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:171
* @route '/procurement/purchase-requests/{purchaseRequest}/submit'
*/
export const submit = (args: { purchaseRequest: string | { id: string } } | [purchaseRequest: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submit.url(args, options),
    method: 'post',
})

submit.definition = {
    methods: ["post"],
    url: '/procurement/purchase-requests/{purchaseRequest}/submit',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::submit
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:171
* @route '/procurement/purchase-requests/{purchaseRequest}/submit'
*/
submit.url = (args: { purchaseRequest: string | { id: string } } | [purchaseRequest: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
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
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:171
* @route '/procurement/purchase-requests/{purchaseRequest}/submit'
*/
submit.post = (args: { purchaseRequest: string | { id: string } } | [purchaseRequest: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submit.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::submit
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:171
* @route '/procurement/purchase-requests/{purchaseRequest}/submit'
*/
const submitForm = (args: { purchaseRequest: string | { id: string } } | [purchaseRequest: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: submit.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::submit
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:171
* @route '/procurement/purchase-requests/{purchaseRequest}/submit'
*/
submitForm.post = (args: { purchaseRequest: string | { id: string } } | [purchaseRequest: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: submit.url(args, options),
    method: 'post',
})

submit.form = submitForm

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::approve
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:188
* @route '/procurement/purchase-requests/{purchaseRequest}/approve'
*/
export const approve = (args: { purchaseRequest: string | { id: string } } | [purchaseRequest: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

approve.definition = {
    methods: ["post"],
    url: '/procurement/purchase-requests/{purchaseRequest}/approve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::approve
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:188
* @route '/procurement/purchase-requests/{purchaseRequest}/approve'
*/
approve.url = (args: { purchaseRequest: string | { id: string } } | [purchaseRequest: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
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
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:188
* @route '/procurement/purchase-requests/{purchaseRequest}/approve'
*/
approve.post = (args: { purchaseRequest: string | { id: string } } | [purchaseRequest: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::approve
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:188
* @route '/procurement/purchase-requests/{purchaseRequest}/approve'
*/
const approveForm = (args: { purchaseRequest: string | { id: string } } | [purchaseRequest: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::approve
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:188
* @route '/procurement/purchase-requests/{purchaseRequest}/approve'
*/
approveForm.post = (args: { purchaseRequest: string | { id: string } } | [purchaseRequest: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
})

approve.form = approveForm

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::reject
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:202
* @route '/procurement/purchase-requests/{purchaseRequest}/reject'
*/
export const reject = (args: { purchaseRequest: string | { id: string } } | [purchaseRequest: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
})

reject.definition = {
    methods: ["post"],
    url: '/procurement/purchase-requests/{purchaseRequest}/reject',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::reject
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:202
* @route '/procurement/purchase-requests/{purchaseRequest}/reject'
*/
reject.url = (args: { purchaseRequest: string | { id: string } } | [purchaseRequest: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
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
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:202
* @route '/procurement/purchase-requests/{purchaseRequest}/reject'
*/
reject.post = (args: { purchaseRequest: string | { id: string } } | [purchaseRequest: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::reject
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:202
* @route '/procurement/purchase-requests/{purchaseRequest}/reject'
*/
const rejectForm = (args: { purchaseRequest: string | { id: string } } | [purchaseRequest: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Procurement\PurchaseRequestController::reject
* @see app/Http/Controllers/Procurement/PurchaseRequestController.php:202
* @route '/procurement/purchase-requests/{purchaseRequest}/reject'
*/
rejectForm.post = (args: { purchaseRequest: string | { id: string } } | [purchaseRequest: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(args, options),
    method: 'post',
})

reject.form = rejectForm

const PurchaseRequestController = { index, create, store, show, submit, approve, reject }

export default PurchaseRequestController