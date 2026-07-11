import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\MemberResignationController::index
* @see app/Http/Controllers/Cooperative/MemberResignationController.php:20
* @route '/cooperative/members/resignations'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cooperative/members/resignations',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\MemberResignationController::index
* @see app/Http/Controllers/Cooperative/MemberResignationController.php:20
* @route '/cooperative/members/resignations'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\MemberResignationController::index
* @see app/Http/Controllers/Cooperative/MemberResignationController.php:20
* @route '/cooperative/members/resignations'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\MemberResignationController::index
* @see app/Http/Controllers/Cooperative/MemberResignationController.php:20
* @route '/cooperative/members/resignations'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\MemberResignationController::process
* @see app/Http/Controllers/Cooperative/MemberResignationController.php:93
* @route '/cooperative/members/resignations/{resignationRequest}/process'
*/
export const process = (args: { resignationRequest: number | { id: number } } | [resignationRequest: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: process.url(args, options),
    method: 'post',
})

process.definition = {
    methods: ["post"],
    url: '/cooperative/members/resignations/{resignationRequest}/process',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\MemberResignationController::process
* @see app/Http/Controllers/Cooperative/MemberResignationController.php:93
* @route '/cooperative/members/resignations/{resignationRequest}/process'
*/
process.url = (args: { resignationRequest: number | { id: number } } | [resignationRequest: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { resignationRequest: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { resignationRequest: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            resignationRequest: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        resignationRequest: typeof args.resignationRequest === 'object'
        ? args.resignationRequest.id
        : args.resignationRequest,
    }

    return process.definition.url
            .replace('{resignationRequest}', parsedArgs.resignationRequest.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\MemberResignationController::process
* @see app/Http/Controllers/Cooperative/MemberResignationController.php:93
* @route '/cooperative/members/resignations/{resignationRequest}/process'
*/
process.post = (args: { resignationRequest: number | { id: number } } | [resignationRequest: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: process.url(args, options),
    method: 'post',
})

const MemberResignationController = { index, process }

export default MemberResignationController