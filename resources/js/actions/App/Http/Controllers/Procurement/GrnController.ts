import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Procurement\GrnController::index
* @see app/Http/Controllers/Procurement/GrnController.php:18
* @route '/procurement/grns'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/procurement/grns',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Procurement\GrnController::index
* @see app/Http/Controllers/Procurement/GrnController.php:18
* @route '/procurement/grns'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Procurement\GrnController::index
* @see app/Http/Controllers/Procurement/GrnController.php:18
* @route '/procurement/grns'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Procurement\GrnController::index
* @see app/Http/Controllers/Procurement/GrnController.php:18
* @route '/procurement/grns'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Procurement\GrnController::index
* @see app/Http/Controllers/Procurement/GrnController.php:18
* @route '/procurement/grns'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Procurement\GrnController::index
* @see app/Http/Controllers/Procurement/GrnController.php:18
* @route '/procurement/grns'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Procurement\GrnController::index
* @see app/Http/Controllers/Procurement/GrnController.php:18
* @route '/procurement/grns'
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
* @see \App\Http\Controllers\Procurement\GrnController::createFromPo
* @see app/Http/Controllers/Procurement/GrnController.php:84
* @route '/procurement/grns/from-po/{purchaseOrder}'
*/
export const createFromPo = (args: { purchaseOrder: string | { id: string } } | [purchaseOrder: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: createFromPo.url(args, options),
    method: 'post',
})

createFromPo.definition = {
    methods: ["post"],
    url: '/procurement/grns/from-po/{purchaseOrder}',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Procurement\GrnController::createFromPo
* @see app/Http/Controllers/Procurement/GrnController.php:84
* @route '/procurement/grns/from-po/{purchaseOrder}'
*/
createFromPo.url = (args: { purchaseOrder: string | { id: string } } | [purchaseOrder: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
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

    return createFromPo.definition.url
            .replace('{purchaseOrder}', parsedArgs.purchaseOrder.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Procurement\GrnController::createFromPo
* @see app/Http/Controllers/Procurement/GrnController.php:84
* @route '/procurement/grns/from-po/{purchaseOrder}'
*/
createFromPo.post = (args: { purchaseOrder: string | { id: string } } | [purchaseOrder: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: createFromPo.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Procurement\GrnController::createFromPo
* @see app/Http/Controllers/Procurement/GrnController.php:84
* @route '/procurement/grns/from-po/{purchaseOrder}'
*/
const createFromPoForm = (args: { purchaseOrder: string | { id: string } } | [purchaseOrder: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: createFromPo.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Procurement\GrnController::createFromPo
* @see app/Http/Controllers/Procurement/GrnController.php:84
* @route '/procurement/grns/from-po/{purchaseOrder}'
*/
createFromPoForm.post = (args: { purchaseOrder: string | { id: string } } | [purchaseOrder: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: createFromPo.url(args, options),
    method: 'post',
})

createFromPo.form = createFromPoForm

/**
* @see \App\Http\Controllers\Procurement\GrnController::show
* @see app/Http/Controllers/Procurement/GrnController.php:42
* @route '/procurement/grns/{goodsReceiveNote}'
*/
export const show = (args: { goodsReceiveNote: string | { id: string } } | [goodsReceiveNote: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/procurement/grns/{goodsReceiveNote}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Procurement\GrnController::show
* @see app/Http/Controllers/Procurement/GrnController.php:42
* @route '/procurement/grns/{goodsReceiveNote}'
*/
show.url = (args: { goodsReceiveNote: string | { id: string } } | [goodsReceiveNote: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { goodsReceiveNote: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { goodsReceiveNote: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            goodsReceiveNote: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        goodsReceiveNote: typeof args.goodsReceiveNote === 'object'
        ? args.goodsReceiveNote.id
        : args.goodsReceiveNote,
    }

    return show.definition.url
            .replace('{goodsReceiveNote}', parsedArgs.goodsReceiveNote.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Procurement\GrnController::show
* @see app/Http/Controllers/Procurement/GrnController.php:42
* @route '/procurement/grns/{goodsReceiveNote}'
*/
show.get = (args: { goodsReceiveNote: string | { id: string } } | [goodsReceiveNote: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Procurement\GrnController::show
* @see app/Http/Controllers/Procurement/GrnController.php:42
* @route '/procurement/grns/{goodsReceiveNote}'
*/
show.head = (args: { goodsReceiveNote: string | { id: string } } | [goodsReceiveNote: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Procurement\GrnController::show
* @see app/Http/Controllers/Procurement/GrnController.php:42
* @route '/procurement/grns/{goodsReceiveNote}'
*/
const showForm = (args: { goodsReceiveNote: string | { id: string } } | [goodsReceiveNote: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Procurement\GrnController::show
* @see app/Http/Controllers/Procurement/GrnController.php:42
* @route '/procurement/grns/{goodsReceiveNote}'
*/
showForm.get = (args: { goodsReceiveNote: string | { id: string } } | [goodsReceiveNote: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Procurement\GrnController::show
* @see app/Http/Controllers/Procurement/GrnController.php:42
* @route '/procurement/grns/{goodsReceiveNote}'
*/
showForm.head = (args: { goodsReceiveNote: string | { id: string } } | [goodsReceiveNote: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\Procurement\GrnController::receive
* @see app/Http/Controllers/Procurement/GrnController.php:95
* @route '/procurement/grns/{goodsReceiveNote}/receive'
*/
export const receive = (args: { goodsReceiveNote: string | { id: string } } | [goodsReceiveNote: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: receive.url(args, options),
    method: 'post',
})

receive.definition = {
    methods: ["post"],
    url: '/procurement/grns/{goodsReceiveNote}/receive',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Procurement\GrnController::receive
* @see app/Http/Controllers/Procurement/GrnController.php:95
* @route '/procurement/grns/{goodsReceiveNote}/receive'
*/
receive.url = (args: { goodsReceiveNote: string | { id: string } } | [goodsReceiveNote: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { goodsReceiveNote: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { goodsReceiveNote: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            goodsReceiveNote: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        goodsReceiveNote: typeof args.goodsReceiveNote === 'object'
        ? args.goodsReceiveNote.id
        : args.goodsReceiveNote,
    }

    return receive.definition.url
            .replace('{goodsReceiveNote}', parsedArgs.goodsReceiveNote.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Procurement\GrnController::receive
* @see app/Http/Controllers/Procurement/GrnController.php:95
* @route '/procurement/grns/{goodsReceiveNote}/receive'
*/
receive.post = (args: { goodsReceiveNote: string | { id: string } } | [goodsReceiveNote: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: receive.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Procurement\GrnController::receive
* @see app/Http/Controllers/Procurement/GrnController.php:95
* @route '/procurement/grns/{goodsReceiveNote}/receive'
*/
const receiveForm = (args: { goodsReceiveNote: string | { id: string } } | [goodsReceiveNote: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: receive.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Procurement\GrnController::receive
* @see app/Http/Controllers/Procurement/GrnController.php:95
* @route '/procurement/grns/{goodsReceiveNote}/receive'
*/
receiveForm.post = (args: { goodsReceiveNote: string | { id: string } } | [goodsReceiveNote: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: receive.url(args, options),
    method: 'post',
})

receive.form = receiveForm

const GrnController = { index, createFromPo, show, receive }

export default GrnController