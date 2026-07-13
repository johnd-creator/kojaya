import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::approve
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:20
* @route '/cooperative/members/{member}/validate'
*/
export const approve = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

approve.definition = {
    methods: ["post"],
    url: '/cooperative/members/{member}/validate',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::approve
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:20
* @route '/cooperative/members/{member}/validate'
*/
approve.url = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { member: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { member: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            member: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        member: typeof args.member === 'object'
        ? args.member.id
        : args.member,
    }

    return approve.definition.url
            .replace('{member}', parsedArgs.member.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::approve
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:20
* @route '/cooperative/members/{member}/validate'
*/
approve.post = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::approve
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:20
* @route '/cooperative/members/{member}/validate'
*/
const approveForm = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::approve
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:20
* @route '/cooperative/members/{member}/validate'
*/
approveForm.post = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
})

approve.form = approveForm

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::approveFinal
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:35
* @route '/cooperative/members/{member}/approve-final'
*/
export const approveFinal = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approveFinal.url(args, options),
    method: 'post',
})

approveFinal.definition = {
    methods: ["post"],
    url: '/cooperative/members/{member}/approve-final',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::approveFinal
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:35
* @route '/cooperative/members/{member}/approve-final'
*/
approveFinal.url = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { member: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { member: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            member: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        member: typeof args.member === 'object'
        ? args.member.id
        : args.member,
    }

    return approveFinal.definition.url
            .replace('{member}', parsedArgs.member.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::approveFinal
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:35
* @route '/cooperative/members/{member}/approve-final'
*/
approveFinal.post = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approveFinal.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::approveFinal
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:35
* @route '/cooperative/members/{member}/approve-final'
*/
const approveFinalForm = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approveFinal.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::approveFinal
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:35
* @route '/cooperative/members/{member}/approve-final'
*/
approveFinalForm.post = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approveFinal.url(args, options),
    method: 'post',
})

approveFinal.form = approveFinalForm

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::requestRevision
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:50
* @route '/cooperative/members/{member}/request-revision'
*/
export const requestRevision = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: requestRevision.url(args, options),
    method: 'post',
})

requestRevision.definition = {
    methods: ["post"],
    url: '/cooperative/members/{member}/request-revision',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::requestRevision
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:50
* @route '/cooperative/members/{member}/request-revision'
*/
requestRevision.url = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { member: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { member: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            member: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        member: typeof args.member === 'object'
        ? args.member.id
        : args.member,
    }

    return requestRevision.definition.url
            .replace('{member}', parsedArgs.member.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::requestRevision
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:50
* @route '/cooperative/members/{member}/request-revision'
*/
requestRevision.post = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: requestRevision.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::requestRevision
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:50
* @route '/cooperative/members/{member}/request-revision'
*/
const requestRevisionForm = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: requestRevision.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::requestRevision
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:50
* @route '/cooperative/members/{member}/request-revision'
*/
requestRevisionForm.post = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: requestRevision.url(args, options),
    method: 'post',
})

requestRevision.form = requestRevisionForm

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::reject
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:63
* @route '/cooperative/members/{member}/reject'
*/
export const reject = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
})

reject.definition = {
    methods: ["post"],
    url: '/cooperative/members/{member}/reject',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::reject
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:63
* @route '/cooperative/members/{member}/reject'
*/
reject.url = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { member: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { member: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            member: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        member: typeof args.member === 'object'
        ? args.member.id
        : args.member,
    }

    return reject.definition.url
            .replace('{member}', parsedArgs.member.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::reject
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:63
* @route '/cooperative/members/{member}/reject'
*/
reject.post = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::reject
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:63
* @route '/cooperative/members/{member}/reject'
*/
const rejectForm = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::reject
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:63
* @route '/cooperative/members/{member}/reject'
*/
rejectForm.post = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(args, options),
    method: 'post',
})

reject.form = rejectForm

const CooperativeMemberValidationController = { approve, approveFinal, requestRevision, reject }

export default CooperativeMemberValidationController