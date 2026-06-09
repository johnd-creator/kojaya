import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Auth\GoogleSsoController::redirect
* @see app/Http/Controllers/Auth/GoogleSsoController.php:22
* @route '/auth/google/redirect'
*/
export const redirect = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: redirect.url(options),
    method: 'get',
})

redirect.definition = {
    methods: ["get","head"],
    url: '/auth/google/redirect',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Auth\GoogleSsoController::redirect
* @see app/Http/Controllers/Auth/GoogleSsoController.php:22
* @route '/auth/google/redirect'
*/
redirect.url = (options?: RouteQueryOptions) => {
    return redirect.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Auth\GoogleSsoController::redirect
* @see app/Http/Controllers/Auth/GoogleSsoController.php:22
* @route '/auth/google/redirect'
*/
redirect.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: redirect.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\GoogleSsoController::redirect
* @see app/Http/Controllers/Auth/GoogleSsoController.php:22
* @route '/auth/google/redirect'
*/
redirect.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: redirect.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Auth\GoogleSsoController::redirect
* @see app/Http/Controllers/Auth/GoogleSsoController.php:22
* @route '/auth/google/redirect'
*/
const redirectForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: redirect.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\GoogleSsoController::redirect
* @see app/Http/Controllers/Auth/GoogleSsoController.php:22
* @route '/auth/google/redirect'
*/
redirectForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: redirect.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\GoogleSsoController::redirect
* @see app/Http/Controllers/Auth/GoogleSsoController.php:22
* @route '/auth/google/redirect'
*/
redirectForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: redirect.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

redirect.form = redirectForm

/**
* @see \App\Http\Controllers\Auth\GoogleSsoController::callback
* @see app/Http/Controllers/Auth/GoogleSsoController.php:62
* @route '/auth/google/callback'
*/
export const callback = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: callback.url(options),
    method: 'get',
})

callback.definition = {
    methods: ["get","head"],
    url: '/auth/google/callback',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Auth\GoogleSsoController::callback
* @see app/Http/Controllers/Auth/GoogleSsoController.php:62
* @route '/auth/google/callback'
*/
callback.url = (options?: RouteQueryOptions) => {
    return callback.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Auth\GoogleSsoController::callback
* @see app/Http/Controllers/Auth/GoogleSsoController.php:62
* @route '/auth/google/callback'
*/
callback.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: callback.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\GoogleSsoController::callback
* @see app/Http/Controllers/Auth/GoogleSsoController.php:62
* @route '/auth/google/callback'
*/
callback.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: callback.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Auth\GoogleSsoController::callback
* @see app/Http/Controllers/Auth/GoogleSsoController.php:62
* @route '/auth/google/callback'
*/
const callbackForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: callback.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\GoogleSsoController::callback
* @see app/Http/Controllers/Auth/GoogleSsoController.php:62
* @route '/auth/google/callback'
*/
callbackForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: callback.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\GoogleSsoController::callback
* @see app/Http/Controllers/Auth/GoogleSsoController.php:62
* @route '/auth/google/callback'
*/
callbackForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: callback.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

callback.form = callbackForm

/**
* @see \App\Http\Controllers\Auth\GoogleSsoController::link
* @see app/Http/Controllers/Auth/GoogleSsoController.php:43
* @route '/auth/google/link'
*/
export const link = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: link.url(options),
    method: 'get',
})

link.definition = {
    methods: ["get","head"],
    url: '/auth/google/link',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Auth\GoogleSsoController::link
* @see app/Http/Controllers/Auth/GoogleSsoController.php:43
* @route '/auth/google/link'
*/
link.url = (options?: RouteQueryOptions) => {
    return link.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Auth\GoogleSsoController::link
* @see app/Http/Controllers/Auth/GoogleSsoController.php:43
* @route '/auth/google/link'
*/
link.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: link.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\GoogleSsoController::link
* @see app/Http/Controllers/Auth/GoogleSsoController.php:43
* @route '/auth/google/link'
*/
link.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: link.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Auth\GoogleSsoController::link
* @see app/Http/Controllers/Auth/GoogleSsoController.php:43
* @route '/auth/google/link'
*/
const linkForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: link.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\GoogleSsoController::link
* @see app/Http/Controllers/Auth/GoogleSsoController.php:43
* @route '/auth/google/link'
*/
linkForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: link.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\GoogleSsoController::link
* @see app/Http/Controllers/Auth/GoogleSsoController.php:43
* @route '/auth/google/link'
*/
linkForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: link.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

link.form = linkForm

const google = {
    redirect: Object.assign(redirect, redirect),
    callback: Object.assign(callback, callback),
    link: Object.assign(link, link),
}

export default google