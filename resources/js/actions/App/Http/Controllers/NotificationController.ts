import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\NotificationController::recent
* @see app/Http/Controllers/NotificationController.php:30
* @route '/api/v1/member/notifications/recent'
*/
const recent007b143d58e575b0f5e75cf58007fd24 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: recent007b143d58e575b0f5e75cf58007fd24.url(options),
    method: 'get',
})

recent007b143d58e575b0f5e75cf58007fd24.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/notifications/recent',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\NotificationController::recent
* @see app/Http/Controllers/NotificationController.php:30
* @route '/api/v1/member/notifications/recent'
*/
recent007b143d58e575b0f5e75cf58007fd24.url = (options?: RouteQueryOptions) => {
    return recent007b143d58e575b0f5e75cf58007fd24.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NotificationController::recent
* @see app/Http/Controllers/NotificationController.php:30
* @route '/api/v1/member/notifications/recent'
*/
recent007b143d58e575b0f5e75cf58007fd24.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: recent007b143d58e575b0f5e75cf58007fd24.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::recent
* @see app/Http/Controllers/NotificationController.php:30
* @route '/api/v1/member/notifications/recent'
*/
recent007b143d58e575b0f5e75cf58007fd24.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: recent007b143d58e575b0f5e75cf58007fd24.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\NotificationController::recent
* @see app/Http/Controllers/NotificationController.php:30
* @route '/api/v1/member/notifications/recent'
*/
const recent007b143d58e575b0f5e75cf58007fd24Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: recent007b143d58e575b0f5e75cf58007fd24.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::recent
* @see app/Http/Controllers/NotificationController.php:30
* @route '/api/v1/member/notifications/recent'
*/
recent007b143d58e575b0f5e75cf58007fd24Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: recent007b143d58e575b0f5e75cf58007fd24.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::recent
* @see app/Http/Controllers/NotificationController.php:30
* @route '/api/v1/member/notifications/recent'
*/
recent007b143d58e575b0f5e75cf58007fd24Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: recent007b143d58e575b0f5e75cf58007fd24.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

recent007b143d58e575b0f5e75cf58007fd24.form = recent007b143d58e575b0f5e75cf58007fd24Form
/**
* @see \App\Http\Controllers\NotificationController::recent
* @see app/Http/Controllers/NotificationController.php:30
* @route '/api/v1/notifications/recent'
*/
const recent5c4350bb915d07ff1524255fc3fae085 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: recent5c4350bb915d07ff1524255fc3fae085.url(options),
    method: 'get',
})

recent5c4350bb915d07ff1524255fc3fae085.definition = {
    methods: ["get","head"],
    url: '/api/v1/notifications/recent',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\NotificationController::recent
* @see app/Http/Controllers/NotificationController.php:30
* @route '/api/v1/notifications/recent'
*/
recent5c4350bb915d07ff1524255fc3fae085.url = (options?: RouteQueryOptions) => {
    return recent5c4350bb915d07ff1524255fc3fae085.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NotificationController::recent
* @see app/Http/Controllers/NotificationController.php:30
* @route '/api/v1/notifications/recent'
*/
recent5c4350bb915d07ff1524255fc3fae085.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: recent5c4350bb915d07ff1524255fc3fae085.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::recent
* @see app/Http/Controllers/NotificationController.php:30
* @route '/api/v1/notifications/recent'
*/
recent5c4350bb915d07ff1524255fc3fae085.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: recent5c4350bb915d07ff1524255fc3fae085.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\NotificationController::recent
* @see app/Http/Controllers/NotificationController.php:30
* @route '/api/v1/notifications/recent'
*/
const recent5c4350bb915d07ff1524255fc3fae085Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: recent5c4350bb915d07ff1524255fc3fae085.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::recent
* @see app/Http/Controllers/NotificationController.php:30
* @route '/api/v1/notifications/recent'
*/
recent5c4350bb915d07ff1524255fc3fae085Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: recent5c4350bb915d07ff1524255fc3fae085.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::recent
* @see app/Http/Controllers/NotificationController.php:30
* @route '/api/v1/notifications/recent'
*/
recent5c4350bb915d07ff1524255fc3fae085Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: recent5c4350bb915d07ff1524255fc3fae085.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

recent5c4350bb915d07ff1524255fc3fae085.form = recent5c4350bb915d07ff1524255fc3fae085Form
/**
* @see \App\Http\Controllers\NotificationController::recent
* @see app/Http/Controllers/NotificationController.php:30
* @route '/api/notifications/recent'
*/
const recent39e6273874e968a21e18ac9808732a87 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: recent39e6273874e968a21e18ac9808732a87.url(options),
    method: 'get',
})

recent39e6273874e968a21e18ac9808732a87.definition = {
    methods: ["get","head"],
    url: '/api/notifications/recent',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\NotificationController::recent
* @see app/Http/Controllers/NotificationController.php:30
* @route '/api/notifications/recent'
*/
recent39e6273874e968a21e18ac9808732a87.url = (options?: RouteQueryOptions) => {
    return recent39e6273874e968a21e18ac9808732a87.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NotificationController::recent
* @see app/Http/Controllers/NotificationController.php:30
* @route '/api/notifications/recent'
*/
recent39e6273874e968a21e18ac9808732a87.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: recent39e6273874e968a21e18ac9808732a87.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::recent
* @see app/Http/Controllers/NotificationController.php:30
* @route '/api/notifications/recent'
*/
recent39e6273874e968a21e18ac9808732a87.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: recent39e6273874e968a21e18ac9808732a87.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\NotificationController::recent
* @see app/Http/Controllers/NotificationController.php:30
* @route '/api/notifications/recent'
*/
const recent39e6273874e968a21e18ac9808732a87Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: recent39e6273874e968a21e18ac9808732a87.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::recent
* @see app/Http/Controllers/NotificationController.php:30
* @route '/api/notifications/recent'
*/
recent39e6273874e968a21e18ac9808732a87Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: recent39e6273874e968a21e18ac9808732a87.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::recent
* @see app/Http/Controllers/NotificationController.php:30
* @route '/api/notifications/recent'
*/
recent39e6273874e968a21e18ac9808732a87Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: recent39e6273874e968a21e18ac9808732a87.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

recent39e6273874e968a21e18ac9808732a87.form = recent39e6273874e968a21e18ac9808732a87Form

export const recent = {
    '/api/v1/member/notifications/recent': recent007b143d58e575b0f5e75cf58007fd24,
    '/api/v1/notifications/recent': recent5c4350bb915d07ff1524255fc3fae085,
    '/api/notifications/recent': recent39e6273874e968a21e18ac9808732a87,
}

/**
* @see \App\Http\Controllers\NotificationController::unreadCount
* @see app/Http/Controllers/NotificationController.php:98
* @route '/api/v1/member/notifications/unread-count'
*/
const unreadCount2fa04cf755d26533e43f07e6f4d9de8f = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: unreadCount2fa04cf755d26533e43f07e6f4d9de8f.url(options),
    method: 'get',
})

unreadCount2fa04cf755d26533e43f07e6f4d9de8f.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/notifications/unread-count',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\NotificationController::unreadCount
* @see app/Http/Controllers/NotificationController.php:98
* @route '/api/v1/member/notifications/unread-count'
*/
unreadCount2fa04cf755d26533e43f07e6f4d9de8f.url = (options?: RouteQueryOptions) => {
    return unreadCount2fa04cf755d26533e43f07e6f4d9de8f.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NotificationController::unreadCount
* @see app/Http/Controllers/NotificationController.php:98
* @route '/api/v1/member/notifications/unread-count'
*/
unreadCount2fa04cf755d26533e43f07e6f4d9de8f.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: unreadCount2fa04cf755d26533e43f07e6f4d9de8f.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::unreadCount
* @see app/Http/Controllers/NotificationController.php:98
* @route '/api/v1/member/notifications/unread-count'
*/
unreadCount2fa04cf755d26533e43f07e6f4d9de8f.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: unreadCount2fa04cf755d26533e43f07e6f4d9de8f.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\NotificationController::unreadCount
* @see app/Http/Controllers/NotificationController.php:98
* @route '/api/v1/member/notifications/unread-count'
*/
const unreadCount2fa04cf755d26533e43f07e6f4d9de8fForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: unreadCount2fa04cf755d26533e43f07e6f4d9de8f.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::unreadCount
* @see app/Http/Controllers/NotificationController.php:98
* @route '/api/v1/member/notifications/unread-count'
*/
unreadCount2fa04cf755d26533e43f07e6f4d9de8fForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: unreadCount2fa04cf755d26533e43f07e6f4d9de8f.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::unreadCount
* @see app/Http/Controllers/NotificationController.php:98
* @route '/api/v1/member/notifications/unread-count'
*/
unreadCount2fa04cf755d26533e43f07e6f4d9de8fForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: unreadCount2fa04cf755d26533e43f07e6f4d9de8f.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

unreadCount2fa04cf755d26533e43f07e6f4d9de8f.form = unreadCount2fa04cf755d26533e43f07e6f4d9de8fForm
/**
* @see \App\Http\Controllers\NotificationController::unreadCount
* @see app/Http/Controllers/NotificationController.php:98
* @route '/api/notifications/unread-count'
*/
const unreadCount361e44804544d11d45d52bcbec6cba32 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: unreadCount361e44804544d11d45d52bcbec6cba32.url(options),
    method: 'get',
})

unreadCount361e44804544d11d45d52bcbec6cba32.definition = {
    methods: ["get","head"],
    url: '/api/notifications/unread-count',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\NotificationController::unreadCount
* @see app/Http/Controllers/NotificationController.php:98
* @route '/api/notifications/unread-count'
*/
unreadCount361e44804544d11d45d52bcbec6cba32.url = (options?: RouteQueryOptions) => {
    return unreadCount361e44804544d11d45d52bcbec6cba32.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NotificationController::unreadCount
* @see app/Http/Controllers/NotificationController.php:98
* @route '/api/notifications/unread-count'
*/
unreadCount361e44804544d11d45d52bcbec6cba32.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: unreadCount361e44804544d11d45d52bcbec6cba32.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::unreadCount
* @see app/Http/Controllers/NotificationController.php:98
* @route '/api/notifications/unread-count'
*/
unreadCount361e44804544d11d45d52bcbec6cba32.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: unreadCount361e44804544d11d45d52bcbec6cba32.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\NotificationController::unreadCount
* @see app/Http/Controllers/NotificationController.php:98
* @route '/api/notifications/unread-count'
*/
const unreadCount361e44804544d11d45d52bcbec6cba32Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: unreadCount361e44804544d11d45d52bcbec6cba32.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::unreadCount
* @see app/Http/Controllers/NotificationController.php:98
* @route '/api/notifications/unread-count'
*/
unreadCount361e44804544d11d45d52bcbec6cba32Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: unreadCount361e44804544d11d45d52bcbec6cba32.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::unreadCount
* @see app/Http/Controllers/NotificationController.php:98
* @route '/api/notifications/unread-count'
*/
unreadCount361e44804544d11d45d52bcbec6cba32Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: unreadCount361e44804544d11d45d52bcbec6cba32.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

unreadCount361e44804544d11d45d52bcbec6cba32.form = unreadCount361e44804544d11d45d52bcbec6cba32Form

export const unreadCount = {
    '/api/v1/member/notifications/unread-count': unreadCount2fa04cf755d26533e43f07e6f4d9de8f,
    '/api/notifications/unread-count': unreadCount361e44804544d11d45d52bcbec6cba32,
}

/**
* @see \App\Http\Controllers\NotificationController::summary
* @see app/Http/Controllers/NotificationController.php:47
* @route '/api/v1/member/notifications/summary'
*/
const summary2ba480964e0f45c9ada39bd07b5d26a3 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: summary2ba480964e0f45c9ada39bd07b5d26a3.url(options),
    method: 'get',
})

summary2ba480964e0f45c9ada39bd07b5d26a3.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/notifications/summary',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\NotificationController::summary
* @see app/Http/Controllers/NotificationController.php:47
* @route '/api/v1/member/notifications/summary'
*/
summary2ba480964e0f45c9ada39bd07b5d26a3.url = (options?: RouteQueryOptions) => {
    return summary2ba480964e0f45c9ada39bd07b5d26a3.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NotificationController::summary
* @see app/Http/Controllers/NotificationController.php:47
* @route '/api/v1/member/notifications/summary'
*/
summary2ba480964e0f45c9ada39bd07b5d26a3.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: summary2ba480964e0f45c9ada39bd07b5d26a3.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::summary
* @see app/Http/Controllers/NotificationController.php:47
* @route '/api/v1/member/notifications/summary'
*/
summary2ba480964e0f45c9ada39bd07b5d26a3.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: summary2ba480964e0f45c9ada39bd07b5d26a3.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\NotificationController::summary
* @see app/Http/Controllers/NotificationController.php:47
* @route '/api/v1/member/notifications/summary'
*/
const summary2ba480964e0f45c9ada39bd07b5d26a3Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: summary2ba480964e0f45c9ada39bd07b5d26a3.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::summary
* @see app/Http/Controllers/NotificationController.php:47
* @route '/api/v1/member/notifications/summary'
*/
summary2ba480964e0f45c9ada39bd07b5d26a3Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: summary2ba480964e0f45c9ada39bd07b5d26a3.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::summary
* @see app/Http/Controllers/NotificationController.php:47
* @route '/api/v1/member/notifications/summary'
*/
summary2ba480964e0f45c9ada39bd07b5d26a3Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: summary2ba480964e0f45c9ada39bd07b5d26a3.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

summary2ba480964e0f45c9ada39bd07b5d26a3.form = summary2ba480964e0f45c9ada39bd07b5d26a3Form
/**
* @see \App\Http\Controllers\NotificationController::summary
* @see app/Http/Controllers/NotificationController.php:47
* @route '/api/v1/notifications/summary'
*/
const summary2ece600570345f6a82d1ef18759b98f9 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: summary2ece600570345f6a82d1ef18759b98f9.url(options),
    method: 'get',
})

summary2ece600570345f6a82d1ef18759b98f9.definition = {
    methods: ["get","head"],
    url: '/api/v1/notifications/summary',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\NotificationController::summary
* @see app/Http/Controllers/NotificationController.php:47
* @route '/api/v1/notifications/summary'
*/
summary2ece600570345f6a82d1ef18759b98f9.url = (options?: RouteQueryOptions) => {
    return summary2ece600570345f6a82d1ef18759b98f9.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NotificationController::summary
* @see app/Http/Controllers/NotificationController.php:47
* @route '/api/v1/notifications/summary'
*/
summary2ece600570345f6a82d1ef18759b98f9.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: summary2ece600570345f6a82d1ef18759b98f9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::summary
* @see app/Http/Controllers/NotificationController.php:47
* @route '/api/v1/notifications/summary'
*/
summary2ece600570345f6a82d1ef18759b98f9.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: summary2ece600570345f6a82d1ef18759b98f9.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\NotificationController::summary
* @see app/Http/Controllers/NotificationController.php:47
* @route '/api/v1/notifications/summary'
*/
const summary2ece600570345f6a82d1ef18759b98f9Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: summary2ece600570345f6a82d1ef18759b98f9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::summary
* @see app/Http/Controllers/NotificationController.php:47
* @route '/api/v1/notifications/summary'
*/
summary2ece600570345f6a82d1ef18759b98f9Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: summary2ece600570345f6a82d1ef18759b98f9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::summary
* @see app/Http/Controllers/NotificationController.php:47
* @route '/api/v1/notifications/summary'
*/
summary2ece600570345f6a82d1ef18759b98f9Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: summary2ece600570345f6a82d1ef18759b98f9.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

summary2ece600570345f6a82d1ef18759b98f9.form = summary2ece600570345f6a82d1ef18759b98f9Form
/**
* @see \App\Http\Controllers\NotificationController::summary
* @see app/Http/Controllers/NotificationController.php:47
* @route '/api/notifications/summary'
*/
const summary80dc801217f451444d430bc0ce2e63df = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: summary80dc801217f451444d430bc0ce2e63df.url(options),
    method: 'get',
})

summary80dc801217f451444d430bc0ce2e63df.definition = {
    methods: ["get","head"],
    url: '/api/notifications/summary',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\NotificationController::summary
* @see app/Http/Controllers/NotificationController.php:47
* @route '/api/notifications/summary'
*/
summary80dc801217f451444d430bc0ce2e63df.url = (options?: RouteQueryOptions) => {
    return summary80dc801217f451444d430bc0ce2e63df.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NotificationController::summary
* @see app/Http/Controllers/NotificationController.php:47
* @route '/api/notifications/summary'
*/
summary80dc801217f451444d430bc0ce2e63df.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: summary80dc801217f451444d430bc0ce2e63df.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::summary
* @see app/Http/Controllers/NotificationController.php:47
* @route '/api/notifications/summary'
*/
summary80dc801217f451444d430bc0ce2e63df.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: summary80dc801217f451444d430bc0ce2e63df.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\NotificationController::summary
* @see app/Http/Controllers/NotificationController.php:47
* @route '/api/notifications/summary'
*/
const summary80dc801217f451444d430bc0ce2e63dfForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: summary80dc801217f451444d430bc0ce2e63df.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::summary
* @see app/Http/Controllers/NotificationController.php:47
* @route '/api/notifications/summary'
*/
summary80dc801217f451444d430bc0ce2e63dfForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: summary80dc801217f451444d430bc0ce2e63df.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::summary
* @see app/Http/Controllers/NotificationController.php:47
* @route '/api/notifications/summary'
*/
summary80dc801217f451444d430bc0ce2e63dfForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: summary80dc801217f451444d430bc0ce2e63df.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

summary80dc801217f451444d430bc0ce2e63df.form = summary80dc801217f451444d430bc0ce2e63dfForm

export const summary = {
    '/api/v1/member/notifications/summary': summary2ba480964e0f45c9ada39bd07b5d26a3,
    '/api/v1/notifications/summary': summary2ece600570345f6a82d1ef18759b98f9,
    '/api/notifications/summary': summary80dc801217f451444d430bc0ce2e63df,
}

/**
* @see \App\Http\Controllers\NotificationController::getPreferences
* @see app/Http/Controllers/NotificationController.php:123
* @route '/api/v1/member/notifications/preferences'
*/
const getPreferences2f40ec8dae2c3550021f8dc7374a0777 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getPreferences2f40ec8dae2c3550021f8dc7374a0777.url(options),
    method: 'get',
})

getPreferences2f40ec8dae2c3550021f8dc7374a0777.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/notifications/preferences',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\NotificationController::getPreferences
* @see app/Http/Controllers/NotificationController.php:123
* @route '/api/v1/member/notifications/preferences'
*/
getPreferences2f40ec8dae2c3550021f8dc7374a0777.url = (options?: RouteQueryOptions) => {
    return getPreferences2f40ec8dae2c3550021f8dc7374a0777.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NotificationController::getPreferences
* @see app/Http/Controllers/NotificationController.php:123
* @route '/api/v1/member/notifications/preferences'
*/
getPreferences2f40ec8dae2c3550021f8dc7374a0777.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getPreferences2f40ec8dae2c3550021f8dc7374a0777.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::getPreferences
* @see app/Http/Controllers/NotificationController.php:123
* @route '/api/v1/member/notifications/preferences'
*/
getPreferences2f40ec8dae2c3550021f8dc7374a0777.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getPreferences2f40ec8dae2c3550021f8dc7374a0777.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\NotificationController::getPreferences
* @see app/Http/Controllers/NotificationController.php:123
* @route '/api/v1/member/notifications/preferences'
*/
const getPreferences2f40ec8dae2c3550021f8dc7374a0777Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getPreferences2f40ec8dae2c3550021f8dc7374a0777.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::getPreferences
* @see app/Http/Controllers/NotificationController.php:123
* @route '/api/v1/member/notifications/preferences'
*/
getPreferences2f40ec8dae2c3550021f8dc7374a0777Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getPreferences2f40ec8dae2c3550021f8dc7374a0777.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::getPreferences
* @see app/Http/Controllers/NotificationController.php:123
* @route '/api/v1/member/notifications/preferences'
*/
getPreferences2f40ec8dae2c3550021f8dc7374a0777Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getPreferences2f40ec8dae2c3550021f8dc7374a0777.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

getPreferences2f40ec8dae2c3550021f8dc7374a0777.form = getPreferences2f40ec8dae2c3550021f8dc7374a0777Form
/**
* @see \App\Http\Controllers\NotificationController::getPreferences
* @see app/Http/Controllers/NotificationController.php:123
* @route '/api/v1/notifications/preferences'
*/
const getPreferences832db66ac341348e524c373f8a26f518 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getPreferences832db66ac341348e524c373f8a26f518.url(options),
    method: 'get',
})

getPreferences832db66ac341348e524c373f8a26f518.definition = {
    methods: ["get","head"],
    url: '/api/v1/notifications/preferences',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\NotificationController::getPreferences
* @see app/Http/Controllers/NotificationController.php:123
* @route '/api/v1/notifications/preferences'
*/
getPreferences832db66ac341348e524c373f8a26f518.url = (options?: RouteQueryOptions) => {
    return getPreferences832db66ac341348e524c373f8a26f518.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NotificationController::getPreferences
* @see app/Http/Controllers/NotificationController.php:123
* @route '/api/v1/notifications/preferences'
*/
getPreferences832db66ac341348e524c373f8a26f518.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getPreferences832db66ac341348e524c373f8a26f518.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::getPreferences
* @see app/Http/Controllers/NotificationController.php:123
* @route '/api/v1/notifications/preferences'
*/
getPreferences832db66ac341348e524c373f8a26f518.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getPreferences832db66ac341348e524c373f8a26f518.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\NotificationController::getPreferences
* @see app/Http/Controllers/NotificationController.php:123
* @route '/api/v1/notifications/preferences'
*/
const getPreferences832db66ac341348e524c373f8a26f518Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getPreferences832db66ac341348e524c373f8a26f518.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::getPreferences
* @see app/Http/Controllers/NotificationController.php:123
* @route '/api/v1/notifications/preferences'
*/
getPreferences832db66ac341348e524c373f8a26f518Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getPreferences832db66ac341348e524c373f8a26f518.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::getPreferences
* @see app/Http/Controllers/NotificationController.php:123
* @route '/api/v1/notifications/preferences'
*/
getPreferences832db66ac341348e524c373f8a26f518Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getPreferences832db66ac341348e524c373f8a26f518.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

getPreferences832db66ac341348e524c373f8a26f518.form = getPreferences832db66ac341348e524c373f8a26f518Form
/**
* @see \App\Http\Controllers\NotificationController::getPreferences
* @see app/Http/Controllers/NotificationController.php:123
* @route '/api/notifications/preferences'
*/
const getPreferences1c9ed59e4f43ba8338c5ab47820a99bf = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getPreferences1c9ed59e4f43ba8338c5ab47820a99bf.url(options),
    method: 'get',
})

getPreferences1c9ed59e4f43ba8338c5ab47820a99bf.definition = {
    methods: ["get","head"],
    url: '/api/notifications/preferences',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\NotificationController::getPreferences
* @see app/Http/Controllers/NotificationController.php:123
* @route '/api/notifications/preferences'
*/
getPreferences1c9ed59e4f43ba8338c5ab47820a99bf.url = (options?: RouteQueryOptions) => {
    return getPreferences1c9ed59e4f43ba8338c5ab47820a99bf.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NotificationController::getPreferences
* @see app/Http/Controllers/NotificationController.php:123
* @route '/api/notifications/preferences'
*/
getPreferences1c9ed59e4f43ba8338c5ab47820a99bf.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getPreferences1c9ed59e4f43ba8338c5ab47820a99bf.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::getPreferences
* @see app/Http/Controllers/NotificationController.php:123
* @route '/api/notifications/preferences'
*/
getPreferences1c9ed59e4f43ba8338c5ab47820a99bf.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getPreferences1c9ed59e4f43ba8338c5ab47820a99bf.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\NotificationController::getPreferences
* @see app/Http/Controllers/NotificationController.php:123
* @route '/api/notifications/preferences'
*/
const getPreferences1c9ed59e4f43ba8338c5ab47820a99bfForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getPreferences1c9ed59e4f43ba8338c5ab47820a99bf.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::getPreferences
* @see app/Http/Controllers/NotificationController.php:123
* @route '/api/notifications/preferences'
*/
getPreferences1c9ed59e4f43ba8338c5ab47820a99bfForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getPreferences1c9ed59e4f43ba8338c5ab47820a99bf.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::getPreferences
* @see app/Http/Controllers/NotificationController.php:123
* @route '/api/notifications/preferences'
*/
getPreferences1c9ed59e4f43ba8338c5ab47820a99bfForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getPreferences1c9ed59e4f43ba8338c5ab47820a99bf.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

getPreferences1c9ed59e4f43ba8338c5ab47820a99bf.form = getPreferences1c9ed59e4f43ba8338c5ab47820a99bfForm

export const getPreferences = {
    '/api/v1/member/notifications/preferences': getPreferences2f40ec8dae2c3550021f8dc7374a0777,
    '/api/v1/notifications/preferences': getPreferences832db66ac341348e524c373f8a26f518,
    '/api/notifications/preferences': getPreferences1c9ed59e4f43ba8338c5ab47820a99bf,
}

/**
* @see \App\Http\Controllers\NotificationController::updatePreferences
* @see app/Http/Controllers/NotificationController.php:107
* @route '/api/v1/member/notifications/preferences'
*/
const updatePreferences2f40ec8dae2c3550021f8dc7374a0777 = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updatePreferences2f40ec8dae2c3550021f8dc7374a0777.url(options),
    method: 'put',
})

updatePreferences2f40ec8dae2c3550021f8dc7374a0777.definition = {
    methods: ["put"],
    url: '/api/v1/member/notifications/preferences',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\NotificationController::updatePreferences
* @see app/Http/Controllers/NotificationController.php:107
* @route '/api/v1/member/notifications/preferences'
*/
updatePreferences2f40ec8dae2c3550021f8dc7374a0777.url = (options?: RouteQueryOptions) => {
    return updatePreferences2f40ec8dae2c3550021f8dc7374a0777.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NotificationController::updatePreferences
* @see app/Http/Controllers/NotificationController.php:107
* @route '/api/v1/member/notifications/preferences'
*/
updatePreferences2f40ec8dae2c3550021f8dc7374a0777.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updatePreferences2f40ec8dae2c3550021f8dc7374a0777.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\NotificationController::updatePreferences
* @see app/Http/Controllers/NotificationController.php:107
* @route '/api/v1/member/notifications/preferences'
*/
const updatePreferences2f40ec8dae2c3550021f8dc7374a0777Form = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updatePreferences2f40ec8dae2c3550021f8dc7374a0777.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\NotificationController::updatePreferences
* @see app/Http/Controllers/NotificationController.php:107
* @route '/api/v1/member/notifications/preferences'
*/
updatePreferences2f40ec8dae2c3550021f8dc7374a0777Form.put = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updatePreferences2f40ec8dae2c3550021f8dc7374a0777.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updatePreferences2f40ec8dae2c3550021f8dc7374a0777.form = updatePreferences2f40ec8dae2c3550021f8dc7374a0777Form
/**
* @see \App\Http\Controllers\NotificationController::updatePreferences
* @see app/Http/Controllers/NotificationController.php:107
* @route '/api/v1/notifications/preferences'
*/
const updatePreferences832db66ac341348e524c373f8a26f518 = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updatePreferences832db66ac341348e524c373f8a26f518.url(options),
    method: 'put',
})

updatePreferences832db66ac341348e524c373f8a26f518.definition = {
    methods: ["put"],
    url: '/api/v1/notifications/preferences',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\NotificationController::updatePreferences
* @see app/Http/Controllers/NotificationController.php:107
* @route '/api/v1/notifications/preferences'
*/
updatePreferences832db66ac341348e524c373f8a26f518.url = (options?: RouteQueryOptions) => {
    return updatePreferences832db66ac341348e524c373f8a26f518.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NotificationController::updatePreferences
* @see app/Http/Controllers/NotificationController.php:107
* @route '/api/v1/notifications/preferences'
*/
updatePreferences832db66ac341348e524c373f8a26f518.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updatePreferences832db66ac341348e524c373f8a26f518.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\NotificationController::updatePreferences
* @see app/Http/Controllers/NotificationController.php:107
* @route '/api/v1/notifications/preferences'
*/
const updatePreferences832db66ac341348e524c373f8a26f518Form = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updatePreferences832db66ac341348e524c373f8a26f518.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\NotificationController::updatePreferences
* @see app/Http/Controllers/NotificationController.php:107
* @route '/api/v1/notifications/preferences'
*/
updatePreferences832db66ac341348e524c373f8a26f518Form.put = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updatePreferences832db66ac341348e524c373f8a26f518.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updatePreferences832db66ac341348e524c373f8a26f518.form = updatePreferences832db66ac341348e524c373f8a26f518Form
/**
* @see \App\Http\Controllers\NotificationController::updatePreferences
* @see app/Http/Controllers/NotificationController.php:107
* @route '/api/notifications/preferences'
*/
const updatePreferences1c9ed59e4f43ba8338c5ab47820a99bf = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updatePreferences1c9ed59e4f43ba8338c5ab47820a99bf.url(options),
    method: 'put',
})

updatePreferences1c9ed59e4f43ba8338c5ab47820a99bf.definition = {
    methods: ["put"],
    url: '/api/notifications/preferences',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\NotificationController::updatePreferences
* @see app/Http/Controllers/NotificationController.php:107
* @route '/api/notifications/preferences'
*/
updatePreferences1c9ed59e4f43ba8338c5ab47820a99bf.url = (options?: RouteQueryOptions) => {
    return updatePreferences1c9ed59e4f43ba8338c5ab47820a99bf.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NotificationController::updatePreferences
* @see app/Http/Controllers/NotificationController.php:107
* @route '/api/notifications/preferences'
*/
updatePreferences1c9ed59e4f43ba8338c5ab47820a99bf.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updatePreferences1c9ed59e4f43ba8338c5ab47820a99bf.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\NotificationController::updatePreferences
* @see app/Http/Controllers/NotificationController.php:107
* @route '/api/notifications/preferences'
*/
const updatePreferences1c9ed59e4f43ba8338c5ab47820a99bfForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updatePreferences1c9ed59e4f43ba8338c5ab47820a99bf.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\NotificationController::updatePreferences
* @see app/Http/Controllers/NotificationController.php:107
* @route '/api/notifications/preferences'
*/
updatePreferences1c9ed59e4f43ba8338c5ab47820a99bfForm.put = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updatePreferences1c9ed59e4f43ba8338c5ab47820a99bf.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updatePreferences1c9ed59e4f43ba8338c5ab47820a99bf.form = updatePreferences1c9ed59e4f43ba8338c5ab47820a99bfForm

export const updatePreferences = {
    '/api/v1/member/notifications/preferences': updatePreferences2f40ec8dae2c3550021f8dc7374a0777,
    '/api/v1/notifications/preferences': updatePreferences832db66ac341348e524c373f8a26f518,
    '/api/notifications/preferences': updatePreferences1c9ed59e4f43ba8338c5ab47820a99bf,
}

/**
* @see \App\Http\Controllers\NotificationController::markAsRead
* @see app/Http/Controllers/NotificationController.php:78
* @route '/api/v1/member/notifications/{notification}/read'
*/
const markAsReadbaa868c53c7a0efcd00aead9d50fdebb = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: markAsReadbaa868c53c7a0efcd00aead9d50fdebb.url(args, options),
    method: 'patch',
})

markAsReadbaa868c53c7a0efcd00aead9d50fdebb.definition = {
    methods: ["patch"],
    url: '/api/v1/member/notifications/{notification}/read',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\NotificationController::markAsRead
* @see app/Http/Controllers/NotificationController.php:78
* @route '/api/v1/member/notifications/{notification}/read'
*/
markAsReadbaa868c53c7a0efcd00aead9d50fdebb.url = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { notification: args }
    }

    if (Array.isArray(args)) {
        args = {
            notification: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        notification: args.notification,
    }

    return markAsReadbaa868c53c7a0efcd00aead9d50fdebb.definition.url
            .replace('{notification}', parsedArgs.notification.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\NotificationController::markAsRead
* @see app/Http/Controllers/NotificationController.php:78
* @route '/api/v1/member/notifications/{notification}/read'
*/
markAsReadbaa868c53c7a0efcd00aead9d50fdebb.patch = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: markAsReadbaa868c53c7a0efcd00aead9d50fdebb.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\NotificationController::markAsRead
* @see app/Http/Controllers/NotificationController.php:78
* @route '/api/v1/member/notifications/{notification}/read'
*/
const markAsReadbaa868c53c7a0efcd00aead9d50fdebbForm = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: markAsReadbaa868c53c7a0efcd00aead9d50fdebb.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\NotificationController::markAsRead
* @see app/Http/Controllers/NotificationController.php:78
* @route '/api/v1/member/notifications/{notification}/read'
*/
markAsReadbaa868c53c7a0efcd00aead9d50fdebbForm.patch = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: markAsReadbaa868c53c7a0efcd00aead9d50fdebb.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

markAsReadbaa868c53c7a0efcd00aead9d50fdebb.form = markAsReadbaa868c53c7a0efcd00aead9d50fdebbForm
/**
* @see \App\Http\Controllers\NotificationController::markAsRead
* @see app/Http/Controllers/NotificationController.php:78
* @route '/api/v1/notifications/{notification}/read'
*/
const markAsRead910e713662a0bbd3bd79c9ad4dcd42a1 = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: markAsRead910e713662a0bbd3bd79c9ad4dcd42a1.url(args, options),
    method: 'patch',
})

markAsRead910e713662a0bbd3bd79c9ad4dcd42a1.definition = {
    methods: ["patch"],
    url: '/api/v1/notifications/{notification}/read',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\NotificationController::markAsRead
* @see app/Http/Controllers/NotificationController.php:78
* @route '/api/v1/notifications/{notification}/read'
*/
markAsRead910e713662a0bbd3bd79c9ad4dcd42a1.url = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { notification: args }
    }

    if (Array.isArray(args)) {
        args = {
            notification: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        notification: args.notification,
    }

    return markAsRead910e713662a0bbd3bd79c9ad4dcd42a1.definition.url
            .replace('{notification}', parsedArgs.notification.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\NotificationController::markAsRead
* @see app/Http/Controllers/NotificationController.php:78
* @route '/api/v1/notifications/{notification}/read'
*/
markAsRead910e713662a0bbd3bd79c9ad4dcd42a1.patch = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: markAsRead910e713662a0bbd3bd79c9ad4dcd42a1.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\NotificationController::markAsRead
* @see app/Http/Controllers/NotificationController.php:78
* @route '/api/v1/notifications/{notification}/read'
*/
const markAsRead910e713662a0bbd3bd79c9ad4dcd42a1Form = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: markAsRead910e713662a0bbd3bd79c9ad4dcd42a1.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\NotificationController::markAsRead
* @see app/Http/Controllers/NotificationController.php:78
* @route '/api/v1/notifications/{notification}/read'
*/
markAsRead910e713662a0bbd3bd79c9ad4dcd42a1Form.patch = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: markAsRead910e713662a0bbd3bd79c9ad4dcd42a1.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

markAsRead910e713662a0bbd3bd79c9ad4dcd42a1.form = markAsRead910e713662a0bbd3bd79c9ad4dcd42a1Form
/**
* @see \App\Http\Controllers\NotificationController::markAsRead
* @see app/Http/Controllers/NotificationController.php:78
* @route '/api/notifications/{id}/read'
*/
const markAsRead4a7d6f40b8464960fd14764336786b53 = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: markAsRead4a7d6f40b8464960fd14764336786b53.url(args, options),
    method: 'patch',
})

markAsRead4a7d6f40b8464960fd14764336786b53.definition = {
    methods: ["patch"],
    url: '/api/notifications/{id}/read',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\NotificationController::markAsRead
* @see app/Http/Controllers/NotificationController.php:78
* @route '/api/notifications/{id}/read'
*/
markAsRead4a7d6f40b8464960fd14764336786b53.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return markAsRead4a7d6f40b8464960fd14764336786b53.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\NotificationController::markAsRead
* @see app/Http/Controllers/NotificationController.php:78
* @route '/api/notifications/{id}/read'
*/
markAsRead4a7d6f40b8464960fd14764336786b53.patch = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: markAsRead4a7d6f40b8464960fd14764336786b53.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\NotificationController::markAsRead
* @see app/Http/Controllers/NotificationController.php:78
* @route '/api/notifications/{id}/read'
*/
const markAsRead4a7d6f40b8464960fd14764336786b53Form = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: markAsRead4a7d6f40b8464960fd14764336786b53.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\NotificationController::markAsRead
* @see app/Http/Controllers/NotificationController.php:78
* @route '/api/notifications/{id}/read'
*/
markAsRead4a7d6f40b8464960fd14764336786b53Form.patch = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: markAsRead4a7d6f40b8464960fd14764336786b53.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

markAsRead4a7d6f40b8464960fd14764336786b53.form = markAsRead4a7d6f40b8464960fd14764336786b53Form

export const markAsRead = {
    '/api/v1/member/notifications/{notification}/read': markAsReadbaa868c53c7a0efcd00aead9d50fdebb,
    '/api/v1/notifications/{notification}/read': markAsRead910e713662a0bbd3bd79c9ad4dcd42a1,
    '/api/notifications/{id}/read': markAsRead4a7d6f40b8464960fd14764336786b53,
}

/**
* @see \App\Http\Controllers\NotificationController::markAllAsRead
* @see app/Http/Controllers/NotificationController.php:88
* @route '/api/v1/member/notifications/mark-all-read'
*/
const markAllAsRead2a113469aa836e8c8f21e4ff870a8c39 = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markAllAsRead2a113469aa836e8c8f21e4ff870a8c39.url(options),
    method: 'post',
})

markAllAsRead2a113469aa836e8c8f21e4ff870a8c39.definition = {
    methods: ["post"],
    url: '/api/v1/member/notifications/mark-all-read',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\NotificationController::markAllAsRead
* @see app/Http/Controllers/NotificationController.php:88
* @route '/api/v1/member/notifications/mark-all-read'
*/
markAllAsRead2a113469aa836e8c8f21e4ff870a8c39.url = (options?: RouteQueryOptions) => {
    return markAllAsRead2a113469aa836e8c8f21e4ff870a8c39.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NotificationController::markAllAsRead
* @see app/Http/Controllers/NotificationController.php:88
* @route '/api/v1/member/notifications/mark-all-read'
*/
markAllAsRead2a113469aa836e8c8f21e4ff870a8c39.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markAllAsRead2a113469aa836e8c8f21e4ff870a8c39.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\NotificationController::markAllAsRead
* @see app/Http/Controllers/NotificationController.php:88
* @route '/api/v1/member/notifications/mark-all-read'
*/
const markAllAsRead2a113469aa836e8c8f21e4ff870a8c39Form = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: markAllAsRead2a113469aa836e8c8f21e4ff870a8c39.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\NotificationController::markAllAsRead
* @see app/Http/Controllers/NotificationController.php:88
* @route '/api/v1/member/notifications/mark-all-read'
*/
markAllAsRead2a113469aa836e8c8f21e4ff870a8c39Form.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: markAllAsRead2a113469aa836e8c8f21e4ff870a8c39.url(options),
    method: 'post',
})

markAllAsRead2a113469aa836e8c8f21e4ff870a8c39.form = markAllAsRead2a113469aa836e8c8f21e4ff870a8c39Form
/**
* @see \App\Http\Controllers\NotificationController::markAllAsRead
* @see app/Http/Controllers/NotificationController.php:88
* @route '/api/v1/notifications/mark-all-read'
*/
const markAllAsReadb3fc6d7260574a79d432a069a5e9286c = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markAllAsReadb3fc6d7260574a79d432a069a5e9286c.url(options),
    method: 'post',
})

markAllAsReadb3fc6d7260574a79d432a069a5e9286c.definition = {
    methods: ["post"],
    url: '/api/v1/notifications/mark-all-read',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\NotificationController::markAllAsRead
* @see app/Http/Controllers/NotificationController.php:88
* @route '/api/v1/notifications/mark-all-read'
*/
markAllAsReadb3fc6d7260574a79d432a069a5e9286c.url = (options?: RouteQueryOptions) => {
    return markAllAsReadb3fc6d7260574a79d432a069a5e9286c.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NotificationController::markAllAsRead
* @see app/Http/Controllers/NotificationController.php:88
* @route '/api/v1/notifications/mark-all-read'
*/
markAllAsReadb3fc6d7260574a79d432a069a5e9286c.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markAllAsReadb3fc6d7260574a79d432a069a5e9286c.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\NotificationController::markAllAsRead
* @see app/Http/Controllers/NotificationController.php:88
* @route '/api/v1/notifications/mark-all-read'
*/
const markAllAsReadb3fc6d7260574a79d432a069a5e9286cForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: markAllAsReadb3fc6d7260574a79d432a069a5e9286c.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\NotificationController::markAllAsRead
* @see app/Http/Controllers/NotificationController.php:88
* @route '/api/v1/notifications/mark-all-read'
*/
markAllAsReadb3fc6d7260574a79d432a069a5e9286cForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: markAllAsReadb3fc6d7260574a79d432a069a5e9286c.url(options),
    method: 'post',
})

markAllAsReadb3fc6d7260574a79d432a069a5e9286c.form = markAllAsReadb3fc6d7260574a79d432a069a5e9286cForm
/**
* @see \App\Http\Controllers\NotificationController::markAllAsRead
* @see app/Http/Controllers/NotificationController.php:88
* @route '/api/notifications/mark-all-read'
*/
const markAllAsReadc9d6b1ec017004dda117fd16da9eb5d3 = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markAllAsReadc9d6b1ec017004dda117fd16da9eb5d3.url(options),
    method: 'post',
})

markAllAsReadc9d6b1ec017004dda117fd16da9eb5d3.definition = {
    methods: ["post"],
    url: '/api/notifications/mark-all-read',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\NotificationController::markAllAsRead
* @see app/Http/Controllers/NotificationController.php:88
* @route '/api/notifications/mark-all-read'
*/
markAllAsReadc9d6b1ec017004dda117fd16da9eb5d3.url = (options?: RouteQueryOptions) => {
    return markAllAsReadc9d6b1ec017004dda117fd16da9eb5d3.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NotificationController::markAllAsRead
* @see app/Http/Controllers/NotificationController.php:88
* @route '/api/notifications/mark-all-read'
*/
markAllAsReadc9d6b1ec017004dda117fd16da9eb5d3.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markAllAsReadc9d6b1ec017004dda117fd16da9eb5d3.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\NotificationController::markAllAsRead
* @see app/Http/Controllers/NotificationController.php:88
* @route '/api/notifications/mark-all-read'
*/
const markAllAsReadc9d6b1ec017004dda117fd16da9eb5d3Form = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: markAllAsReadc9d6b1ec017004dda117fd16da9eb5d3.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\NotificationController::markAllAsRead
* @see app/Http/Controllers/NotificationController.php:88
* @route '/api/notifications/mark-all-read'
*/
markAllAsReadc9d6b1ec017004dda117fd16da9eb5d3Form.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: markAllAsReadc9d6b1ec017004dda117fd16da9eb5d3.url(options),
    method: 'post',
})

markAllAsReadc9d6b1ec017004dda117fd16da9eb5d3.form = markAllAsReadc9d6b1ec017004dda117fd16da9eb5d3Form

export const markAllAsRead = {
    '/api/v1/member/notifications/mark-all-read': markAllAsRead2a113469aa836e8c8f21e4ff870a8c39,
    '/api/v1/notifications/mark-all-read': markAllAsReadb3fc6d7260574a79d432a069a5e9286c,
    '/api/notifications/mark-all-read': markAllAsReadc9d6b1ec017004dda117fd16da9eb5d3,
}

/**
* @see \App\Http\Controllers\NotificationController::index
* @see app/Http/Controllers/NotificationController.php:21
* @route '/api/v1/notifications'
*/
const index61390cf35a89fe10cc418b5300acba9f = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index61390cf35a89fe10cc418b5300acba9f.url(options),
    method: 'get',
})

index61390cf35a89fe10cc418b5300acba9f.definition = {
    methods: ["get","head"],
    url: '/api/v1/notifications',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\NotificationController::index
* @see app/Http/Controllers/NotificationController.php:21
* @route '/api/v1/notifications'
*/
index61390cf35a89fe10cc418b5300acba9f.url = (options?: RouteQueryOptions) => {
    return index61390cf35a89fe10cc418b5300acba9f.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NotificationController::index
* @see app/Http/Controllers/NotificationController.php:21
* @route '/api/v1/notifications'
*/
index61390cf35a89fe10cc418b5300acba9f.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index61390cf35a89fe10cc418b5300acba9f.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::index
* @see app/Http/Controllers/NotificationController.php:21
* @route '/api/v1/notifications'
*/
index61390cf35a89fe10cc418b5300acba9f.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index61390cf35a89fe10cc418b5300acba9f.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\NotificationController::index
* @see app/Http/Controllers/NotificationController.php:21
* @route '/api/v1/notifications'
*/
const index61390cf35a89fe10cc418b5300acba9fForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index61390cf35a89fe10cc418b5300acba9f.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::index
* @see app/Http/Controllers/NotificationController.php:21
* @route '/api/v1/notifications'
*/
index61390cf35a89fe10cc418b5300acba9fForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index61390cf35a89fe10cc418b5300acba9f.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::index
* @see app/Http/Controllers/NotificationController.php:21
* @route '/api/v1/notifications'
*/
index61390cf35a89fe10cc418b5300acba9fForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index61390cf35a89fe10cc418b5300acba9f.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index61390cf35a89fe10cc418b5300acba9f.form = index61390cf35a89fe10cc418b5300acba9fForm
/**
* @see \App\Http\Controllers\NotificationController::index
* @see app/Http/Controllers/NotificationController.php:21
* @route '/api/notifications'
*/
const index63ca617bad575304d9a46c7bd2661780 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index63ca617bad575304d9a46c7bd2661780.url(options),
    method: 'get',
})

index63ca617bad575304d9a46c7bd2661780.definition = {
    methods: ["get","head"],
    url: '/api/notifications',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\NotificationController::index
* @see app/Http/Controllers/NotificationController.php:21
* @route '/api/notifications'
*/
index63ca617bad575304d9a46c7bd2661780.url = (options?: RouteQueryOptions) => {
    return index63ca617bad575304d9a46c7bd2661780.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NotificationController::index
* @see app/Http/Controllers/NotificationController.php:21
* @route '/api/notifications'
*/
index63ca617bad575304d9a46c7bd2661780.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index63ca617bad575304d9a46c7bd2661780.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::index
* @see app/Http/Controllers/NotificationController.php:21
* @route '/api/notifications'
*/
index63ca617bad575304d9a46c7bd2661780.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index63ca617bad575304d9a46c7bd2661780.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\NotificationController::index
* @see app/Http/Controllers/NotificationController.php:21
* @route '/api/notifications'
*/
const index63ca617bad575304d9a46c7bd2661780Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index63ca617bad575304d9a46c7bd2661780.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::index
* @see app/Http/Controllers/NotificationController.php:21
* @route '/api/notifications'
*/
index63ca617bad575304d9a46c7bd2661780Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index63ca617bad575304d9a46c7bd2661780.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::index
* @see app/Http/Controllers/NotificationController.php:21
* @route '/api/notifications'
*/
index63ca617bad575304d9a46c7bd2661780Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index63ca617bad575304d9a46c7bd2661780.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index63ca617bad575304d9a46c7bd2661780.form = index63ca617bad575304d9a46c7bd2661780Form

export const index = {
    '/api/v1/notifications': index61390cf35a89fe10cc418b5300acba9f,
    '/api/notifications': index63ca617bad575304d9a46c7bd2661780,
}

/**
* @see \App\Http\Controllers\NotificationController::show
* @see app/Http/Controllers/NotificationController.php:68
* @route '/api/notifications/{id}'
*/
export const show = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/notifications/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\NotificationController::show
* @see app/Http/Controllers/NotificationController.php:68
* @route '/api/notifications/{id}'
*/
show.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return show.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\NotificationController::show
* @see app/Http/Controllers/NotificationController.php:68
* @route '/api/notifications/{id}'
*/
show.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::show
* @see app/Http/Controllers/NotificationController.php:68
* @route '/api/notifications/{id}'
*/
show.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\NotificationController::show
* @see app/Http/Controllers/NotificationController.php:68
* @route '/api/notifications/{id}'
*/
const showForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::show
* @see app/Http/Controllers/NotificationController.php:68
* @route '/api/notifications/{id}'
*/
showForm.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::show
* @see app/Http/Controllers/NotificationController.php:68
* @route '/api/notifications/{id}'
*/
showForm.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const NotificationController = { recent, unreadCount, summary, getPreferences, updatePreferences, markAsRead, markAllAsRead, index, show }

export default NotificationController