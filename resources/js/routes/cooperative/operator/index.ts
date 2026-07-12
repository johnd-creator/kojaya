import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
import closing00a277 from './closing'
import payments from './payments'
/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::dashboard
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:23
* @route '/cooperative/operator/dashboard'
*/
export const dashboard = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

dashboard.definition = {
    methods: ["get","head"],
    url: '/cooperative/operator/dashboard',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::dashboard
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:23
* @route '/cooperative/operator/dashboard'
*/
dashboard.url = (options?: RouteQueryOptions) => {
    return dashboard.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::dashboard
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:23
* @route '/cooperative/operator/dashboard'
*/
dashboard.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::dashboard
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:23
* @route '/cooperative/operator/dashboard'
*/
dashboard.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dashboard.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::approvalInbox
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:32
* @route '/cooperative/operator/approval-inbox'
*/
export const approvalInbox = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: approvalInbox.url(options),
    method: 'get',
})

approvalInbox.definition = {
    methods: ["get","head"],
    url: '/cooperative/operator/approval-inbox',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::approvalInbox
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:32
* @route '/cooperative/operator/approval-inbox'
*/
approvalInbox.url = (options?: RouteQueryOptions) => {
    return approvalInbox.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::approvalInbox
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:32
* @route '/cooperative/operator/approval-inbox'
*/
approvalInbox.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: approvalInbox.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::approvalInbox
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:32
* @route '/cooperative/operator/approval-inbox'
*/
approvalInbox.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: approvalInbox.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::exceptions
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:39
* @route '/cooperative/operator/exceptions'
*/
export const exceptions = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exceptions.url(options),
    method: 'get',
})

exceptions.definition = {
    methods: ["get","head"],
    url: '/cooperative/operator/exceptions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::exceptions
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:39
* @route '/cooperative/operator/exceptions'
*/
exceptions.url = (options?: RouteQueryOptions) => {
    return exceptions.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::exceptions
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:39
* @route '/cooperative/operator/exceptions'
*/
exceptions.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exceptions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::exceptions
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:39
* @route '/cooperative/operator/exceptions'
*/
exceptions.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: exceptions.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::analytics
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:46
* @route '/cooperative/operator/analytics'
*/
export const analytics = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: analytics.url(options),
    method: 'get',
})

analytics.definition = {
    methods: ["get","head"],
    url: '/cooperative/operator/analytics',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::analytics
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:46
* @route '/cooperative/operator/analytics'
*/
analytics.url = (options?: RouteQueryOptions) => {
    return analytics.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::analytics
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:46
* @route '/cooperative/operator/analytics'
*/
analytics.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: analytics.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::analytics
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:46
* @route '/cooperative/operator/analytics'
*/
analytics.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: analytics.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::closing
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:53
* @route '/cooperative/operator/closing'
*/
export const closing = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: closing.url(options),
    method: 'get',
})

closing.definition = {
    methods: ["get","head"],
    url: '/cooperative/operator/closing',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::closing
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:53
* @route '/cooperative/operator/closing'
*/
closing.url = (options?: RouteQueryOptions) => {
    return closing.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::closing
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:53
* @route '/cooperative/operator/closing'
*/
closing.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: closing.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::closing
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:53
* @route '/cooperative/operator/closing'
*/
closing.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: closing.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::exportMethod
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:113
* @route '/cooperative/operator/export'
*/
export const exportMethod = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportMethod.url(options),
    method: 'get',
})

exportMethod.definition = {
    methods: ["get","head"],
    url: '/cooperative/operator/export',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::exportMethod
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:113
* @route '/cooperative/operator/export'
*/
exportMethod.url = (options?: RouteQueryOptions) => {
    return exportMethod.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::exportMethod
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:113
* @route '/cooperative/operator/export'
*/
exportMethod.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportMethod.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::exportMethod
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:113
* @route '/cooperative/operator/export'
*/
exportMethod.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: exportMethod.url(options),
    method: 'head',
})

const operator = {
    dashboard: Object.assign(dashboard, dashboard),
    approvalInbox: Object.assign(approvalInbox, approvalInbox),
    exceptions: Object.assign(exceptions, exceptions),
    analytics: Object.assign(analytics, analytics),
    closing: Object.assign(closing, closing00a277),
    payments: Object.assign(payments, payments),
    export: Object.assign(exportMethod, exportMethod),
}

export default operator