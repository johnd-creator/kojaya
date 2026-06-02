import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\AuthController::login
* @see app/Http/Controllers/Api/AuthController.php:19
* @route '/api/auth/login'
*/
export const login = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: login.url(options),
    method: 'post',
})

login.definition = {
    methods: ["post"],
    url: '/api/auth/login',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\AuthController::login
* @see app/Http/Controllers/Api/AuthController.php:19
* @route '/api/auth/login'
*/
login.url = (options?: RouteQueryOptions) => {
    return login.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\AuthController::login
* @see app/Http/Controllers/Api/AuthController.php:19
* @route '/api/auth/login'
*/
login.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: login.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\AuthController::login
* @see app/Http/Controllers/Api/AuthController.php:19
* @route '/api/auth/login'
*/
const loginForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: login.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\AuthController::login
* @see app/Http/Controllers/Api/AuthController.php:19
* @route '/api/auth/login'
*/
loginForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: login.url(options),
    method: 'post',
})

login.form = loginForm

/**
* @see \App\Http\Controllers\Api\AuthController::session
* @see app/Http/Controllers/Api/AuthController.php:46
* @route '/api/auth/session'
*/
export const session = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: session.url(options),
    method: 'get',
})

session.definition = {
    methods: ["get","head"],
    url: '/api/auth/session',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\AuthController::session
* @see app/Http/Controllers/Api/AuthController.php:46
* @route '/api/auth/session'
*/
session.url = (options?: RouteQueryOptions) => {
    return session.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\AuthController::session
* @see app/Http/Controllers/Api/AuthController.php:46
* @route '/api/auth/session'
*/
session.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: session.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\AuthController::session
* @see app/Http/Controllers/Api/AuthController.php:46
* @route '/api/auth/session'
*/
session.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: session.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\AuthController::session
* @see app/Http/Controllers/Api/AuthController.php:46
* @route '/api/auth/session'
*/
const sessionForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: session.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\AuthController::session
* @see app/Http/Controllers/Api/AuthController.php:46
* @route '/api/auth/session'
*/
sessionForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: session.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\AuthController::session
* @see app/Http/Controllers/Api/AuthController.php:46
* @route '/api/auth/session'
*/
sessionForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: session.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

session.form = sessionForm

/**
* @see \App\Http\Controllers\Api\AuthController::logout
* @see app/Http/Controllers/Api/AuthController.php:61
* @route '/api/auth/logout'
*/
export const logout = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logout.url(options),
    method: 'post',
})

logout.definition = {
    methods: ["post"],
    url: '/api/auth/logout',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\AuthController::logout
* @see app/Http/Controllers/Api/AuthController.php:61
* @route '/api/auth/logout'
*/
logout.url = (options?: RouteQueryOptions) => {
    return logout.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\AuthController::logout
* @see app/Http/Controllers/Api/AuthController.php:61
* @route '/api/auth/logout'
*/
logout.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logout.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\AuthController::logout
* @see app/Http/Controllers/Api/AuthController.php:61
* @route '/api/auth/logout'
*/
const logoutForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: logout.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\AuthController::logout
* @see app/Http/Controllers/Api/AuthController.php:61
* @route '/api/auth/logout'
*/
logoutForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: logout.url(options),
    method: 'post',
})

logout.form = logoutForm

/**
* @see \App\Http\Controllers\Api\AuthController::logoutAll
* @see app/Http/Controllers/Api/AuthController.php:72
* @route '/api/auth/logout-all'
*/
export const logoutAll = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logoutAll.url(options),
    method: 'post',
})

logoutAll.definition = {
    methods: ["post"],
    url: '/api/auth/logout-all',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\AuthController::logoutAll
* @see app/Http/Controllers/Api/AuthController.php:72
* @route '/api/auth/logout-all'
*/
logoutAll.url = (options?: RouteQueryOptions) => {
    return logoutAll.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\AuthController::logoutAll
* @see app/Http/Controllers/Api/AuthController.php:72
* @route '/api/auth/logout-all'
*/
logoutAll.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logoutAll.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\AuthController::logoutAll
* @see app/Http/Controllers/Api/AuthController.php:72
* @route '/api/auth/logout-all'
*/
const logoutAllForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: logoutAll.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\AuthController::logoutAll
* @see app/Http/Controllers/Api/AuthController.php:72
* @route '/api/auth/logout-all'
*/
logoutAllForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: logoutAll.url(options),
    method: 'post',
})

logoutAll.form = logoutAllForm

const AuthController = { login, session, logout, logoutAll }

export default AuthController