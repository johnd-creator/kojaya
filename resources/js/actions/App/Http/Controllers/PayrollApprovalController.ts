import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\PayrollApprovalController::index
* @see app/Http/Controllers/PayrollApprovalController.php:16
* @route '/payroll-approvals'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/payroll-approvals',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PayrollApprovalController::index
* @see app/Http/Controllers/PayrollApprovalController.php:16
* @route '/payroll-approvals'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PayrollApprovalController::index
* @see app/Http/Controllers/PayrollApprovalController.php:16
* @route '/payroll-approvals'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PayrollApprovalController::index
* @see app/Http/Controllers/PayrollApprovalController.php:16
* @route '/payroll-approvals'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PayrollApprovalController::index
* @see app/Http/Controllers/PayrollApprovalController.php:16
* @route '/payroll-approvals'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PayrollApprovalController::index
* @see app/Http/Controllers/PayrollApprovalController.php:16
* @route '/payroll-approvals'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PayrollApprovalController::index
* @see app/Http/Controllers/PayrollApprovalController.php:16
* @route '/payroll-approvals'
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
* @see \App\Http\Controllers\PayrollApprovalController::approve
* @see app/Http/Controllers/PayrollApprovalController.php:46
* @route '/payroll-approvals/{approval}/approve'
*/
export const approve = (args: { approval: string | number | { id: string | number } } | [approval: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

approve.definition = {
    methods: ["post"],
    url: '/payroll-approvals/{approval}/approve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PayrollApprovalController::approve
* @see app/Http/Controllers/PayrollApprovalController.php:46
* @route '/payroll-approvals/{approval}/approve'
*/
approve.url = (args: { approval: string | number | { id: string | number } } | [approval: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { approval: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { approval: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            approval: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        approval: typeof args.approval === 'object'
        ? args.approval.id
        : args.approval,
    }

    return approve.definition.url
            .replace('{approval}', parsedArgs.approval.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PayrollApprovalController::approve
* @see app/Http/Controllers/PayrollApprovalController.php:46
* @route '/payroll-approvals/{approval}/approve'
*/
approve.post = (args: { approval: string | number | { id: string | number } } | [approval: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PayrollApprovalController::approve
* @see app/Http/Controllers/PayrollApprovalController.php:46
* @route '/payroll-approvals/{approval}/approve'
*/
const approveForm = (args: { approval: string | number | { id: string | number } } | [approval: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PayrollApprovalController::approve
* @see app/Http/Controllers/PayrollApprovalController.php:46
* @route '/payroll-approvals/{approval}/approve'
*/
approveForm.post = (args: { approval: string | number | { id: string | number } } | [approval: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
})

approve.form = approveForm

/**
* @see \App\Http\Controllers\PayrollApprovalController::reject
* @see app/Http/Controllers/PayrollApprovalController.php:59
* @route '/payroll-approvals/{approval}/reject'
*/
export const reject = (args: { approval: string | number | { id: string | number } } | [approval: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
})

reject.definition = {
    methods: ["post"],
    url: '/payroll-approvals/{approval}/reject',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PayrollApprovalController::reject
* @see app/Http/Controllers/PayrollApprovalController.php:59
* @route '/payroll-approvals/{approval}/reject'
*/
reject.url = (args: { approval: string | number | { id: string | number } } | [approval: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { approval: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { approval: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            approval: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        approval: typeof args.approval === 'object'
        ? args.approval.id
        : args.approval,
    }

    return reject.definition.url
            .replace('{approval}', parsedArgs.approval.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PayrollApprovalController::reject
* @see app/Http/Controllers/PayrollApprovalController.php:59
* @route '/payroll-approvals/{approval}/reject'
*/
reject.post = (args: { approval: string | number | { id: string | number } } | [approval: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PayrollApprovalController::reject
* @see app/Http/Controllers/PayrollApprovalController.php:59
* @route '/payroll-approvals/{approval}/reject'
*/
const rejectForm = (args: { approval: string | number | { id: string | number } } | [approval: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PayrollApprovalController::reject
* @see app/Http/Controllers/PayrollApprovalController.php:59
* @route '/payroll-approvals/{approval}/reject'
*/
rejectForm.post = (args: { approval: string | number | { id: string | number } } | [approval: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(args, options),
    method: 'post',
})

reject.form = rejectForm

const PayrollApprovalController = { index, approve, reject }

export default PayrollApprovalController