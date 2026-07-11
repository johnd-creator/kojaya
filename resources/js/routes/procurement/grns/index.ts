import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
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
* @see \App\Http\Controllers\Procurement\GrnController::fromPo
* @see app/Http/Controllers/Procurement/GrnController.php:90
* @route '/procurement/grns/from-po/{purchaseOrder}'
*/
export const fromPo = (args: { purchaseOrder: string | { id: string } } | [purchaseOrder: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: fromPo.url(args, options),
    method: 'post',
})

fromPo.definition = {
    methods: ["post"],
    url: '/procurement/grns/from-po/{purchaseOrder}',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Procurement\GrnController::fromPo
* @see app/Http/Controllers/Procurement/GrnController.php:90
* @route '/procurement/grns/from-po/{purchaseOrder}'
*/
fromPo.url = (args: { purchaseOrder: string | { id: string } } | [purchaseOrder: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
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

    return fromPo.definition.url
            .replace('{purchaseOrder}', parsedArgs.purchaseOrder.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Procurement\GrnController::fromPo
* @see app/Http/Controllers/Procurement/GrnController.php:90
* @route '/procurement/grns/from-po/{purchaseOrder}'
*/
fromPo.post = (args: { purchaseOrder: string | { id: string } } | [purchaseOrder: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: fromPo.url(args, options),
    method: 'post',
})

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
* @see \App\Http\Controllers\Procurement\GrnController::receive
* @see app/Http/Controllers/Procurement/GrnController.php:101
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
* @see app/Http/Controllers/Procurement/GrnController.php:101
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
* @see app/Http/Controllers/Procurement/GrnController.php:101
* @route '/procurement/grns/{goodsReceiveNote}/receive'
*/
receive.post = (args: { goodsReceiveNote: string | { id: string } } | [goodsReceiveNote: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: receive.url(args, options),
    method: 'post',
})

const grns = {
    index: Object.assign(index, index),
    fromPo: Object.assign(fromPo, fromPo),
    show: Object.assign(show, show),
    receive: Object.assign(receive, receive),
}

export default grns