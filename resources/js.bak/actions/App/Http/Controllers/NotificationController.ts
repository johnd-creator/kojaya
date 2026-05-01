import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\NotificationController::index
* @see app/Http/Controllers/NotificationController.php:18
* @route '/api/notifications'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/notifications',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\NotificationController::index
* @see app/Http/Controllers/NotificationController.php:18
* @route '/api/notifications'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NotificationController::index
* @see app/Http/Controllers/NotificationController.php:18
* @route '/api/notifications'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::index
* @see app/Http/Controllers/NotificationController.php:18
* @route '/api/notifications'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\NotificationController::show
* @see app/Http/Controllers/NotificationController.php:28
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
* @see app/Http/Controllers/NotificationController.php:28
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
* @see app/Http/Controllers/NotificationController.php:28
* @route '/api/notifications/{id}'
*/
show.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::show
* @see app/Http/Controllers/NotificationController.php:28
* @route '/api/notifications/{id}'
*/
show.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\NotificationController::markAsRead
* @see app/Http/Controllers/NotificationController.php:38
* @route '/api/notifications/{id}/read'
*/
export const markAsRead = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: markAsRead.url(args, options),
    method: 'patch',
})

markAsRead.definition = {
    methods: ["patch"],
    url: '/api/notifications/{id}/read',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\NotificationController::markAsRead
* @see app/Http/Controllers/NotificationController.php:38
* @route '/api/notifications/{id}/read'
*/
markAsRead.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return markAsRead.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\NotificationController::markAsRead
* @see app/Http/Controllers/NotificationController.php:38
* @route '/api/notifications/{id}/read'
*/
markAsRead.patch = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: markAsRead.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\NotificationController::markAllAsRead
* @see app/Http/Controllers/NotificationController.php:48
* @route '/api/notifications/mark-all-read'
*/
export const markAllAsRead = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markAllAsRead.url(options),
    method: 'post',
})

markAllAsRead.definition = {
    methods: ["post"],
    url: '/api/notifications/mark-all-read',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\NotificationController::markAllAsRead
* @see app/Http/Controllers/NotificationController.php:48
* @route '/api/notifications/mark-all-read'
*/
markAllAsRead.url = (options?: RouteQueryOptions) => {
    return markAllAsRead.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NotificationController::markAllAsRead
* @see app/Http/Controllers/NotificationController.php:48
* @route '/api/notifications/mark-all-read'
*/
markAllAsRead.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markAllAsRead.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\NotificationController::unreadCount
* @see app/Http/Controllers/NotificationController.php:58
* @route '/api/notifications/unread-count'
*/
export const unreadCount = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: unreadCount.url(options),
    method: 'get',
})

unreadCount.definition = {
    methods: ["get","head"],
    url: '/api/notifications/unread-count',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\NotificationController::unreadCount
* @see app/Http/Controllers/NotificationController.php:58
* @route '/api/notifications/unread-count'
*/
unreadCount.url = (options?: RouteQueryOptions) => {
    return unreadCount.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NotificationController::unreadCount
* @see app/Http/Controllers/NotificationController.php:58
* @route '/api/notifications/unread-count'
*/
unreadCount.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: unreadCount.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::unreadCount
* @see app/Http/Controllers/NotificationController.php:58
* @route '/api/notifications/unread-count'
*/
unreadCount.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: unreadCount.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\NotificationController::updatePreferences
* @see app/Http/Controllers/NotificationController.php:67
* @route '/api/notifications/preferences'
*/
export const updatePreferences = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updatePreferences.url(options),
    method: 'put',
})

updatePreferences.definition = {
    methods: ["put"],
    url: '/api/notifications/preferences',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\NotificationController::updatePreferences
* @see app/Http/Controllers/NotificationController.php:67
* @route '/api/notifications/preferences'
*/
updatePreferences.url = (options?: RouteQueryOptions) => {
    return updatePreferences.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NotificationController::updatePreferences
* @see app/Http/Controllers/NotificationController.php:67
* @route '/api/notifications/preferences'
*/
updatePreferences.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updatePreferences.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\NotificationController::getPreferences
* @see app/Http/Controllers/NotificationController.php:88
* @route '/api/notifications/preferences'
*/
export const getPreferences = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getPreferences.url(options),
    method: 'get',
})

getPreferences.definition = {
    methods: ["get","head"],
    url: '/api/notifications/preferences',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\NotificationController::getPreferences
* @see app/Http/Controllers/NotificationController.php:88
* @route '/api/notifications/preferences'
*/
getPreferences.url = (options?: RouteQueryOptions) => {
    return getPreferences.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NotificationController::getPreferences
* @see app/Http/Controllers/NotificationController.php:88
* @route '/api/notifications/preferences'
*/
getPreferences.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getPreferences.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\NotificationController::getPreferences
* @see app/Http/Controllers/NotificationController.php:88
* @route '/api/notifications/preferences'
*/
getPreferences.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getPreferences.url(options),
    method: 'head',
})

const NotificationController = { index, show, markAsRead, markAllAsRead, unreadCount, updatePreferences, getPreferences }

export default NotificationController