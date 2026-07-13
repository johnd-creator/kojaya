import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\AuthController::login
* @see app/Http/Controllers/Api/AuthController.php:28
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
* @see app/Http/Controllers/Api/AuthController.php:28
* @route '/api/auth/login'
*/
login.url = (options?: RouteQueryOptions) => {
    return login.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\AuthController::login
* @see app/Http/Controllers/Api/AuthController.php:28
* @route '/api/auth/login'
*/
login.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: login.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\AuthController::loginWithGoogle
* @see app/Http/Controllers/Api/AuthController.php:98
* @route '/api/auth/google/mobile'
*/
export const loginWithGoogle = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: loginWithGoogle.url(options),
    method: 'post',
})

loginWithGoogle.definition = {
    methods: ["post"],
    url: '/api/auth/google/mobile',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\AuthController::loginWithGoogle
* @see app/Http/Controllers/Api/AuthController.php:98
* @route '/api/auth/google/mobile'
*/
loginWithGoogle.url = (options?: RouteQueryOptions) => {
    return loginWithGoogle.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\AuthController::loginWithGoogle
* @see app/Http/Controllers/Api/AuthController.php:98
* @route '/api/auth/google/mobile'
*/
loginWithGoogle.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: loginWithGoogle.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\AuthController::session
* @see app/Http/Controllers/Api/AuthController.php:55
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
* @see app/Http/Controllers/Api/AuthController.php:55
* @route '/api/auth/session'
*/
session.url = (options?: RouteQueryOptions) => {
    return session.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\AuthController::session
* @see app/Http/Controllers/Api/AuthController.php:55
* @route '/api/auth/session'
*/
session.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: session.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\AuthController::session
* @see app/Http/Controllers/Api/AuthController.php:55
* @route '/api/auth/session'
*/
session.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: session.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\AuthController::logout
* @see app/Http/Controllers/Api/AuthController.php:70
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
* @see app/Http/Controllers/Api/AuthController.php:70
* @route '/api/auth/logout'
*/
logout.url = (options?: RouteQueryOptions) => {
    return logout.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\AuthController::logout
* @see app/Http/Controllers/Api/AuthController.php:70
* @route '/api/auth/logout'
*/
logout.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logout.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\AuthController::logoutAll
* @see app/Http/Controllers/Api/AuthController.php:81
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
* @see app/Http/Controllers/Api/AuthController.php:81
* @route '/api/auth/logout-all'
*/
logoutAll.url = (options?: RouteQueryOptions) => {
    return logoutAll.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\AuthController::logoutAll
* @see app/Http/Controllers/Api/AuthController.php:81
* @route '/api/auth/logout-all'
*/
logoutAll.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logoutAll.url(options),
    method: 'post',
})

const AuthController = { login, loginWithGoogle, session, logout, logoutAll }

export default AuthController