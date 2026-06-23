import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::reconcile
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:103
* @route '/cooperative/operator/payments/{payment}/reconcile'
*/
export const reconcile = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reconcile.url(args, options),
    method: 'post',
})

reconcile.definition = {
    methods: ["post"],
    url: '/cooperative/operator/payments/{payment}/reconcile',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::reconcile
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:103
* @route '/cooperative/operator/payments/{payment}/reconcile'
*/
reconcile.url = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { payment: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { payment: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            payment: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        payment: typeof args.payment === 'object'
        ? args.payment.id
        : args.payment,
    }

    return reconcile.definition.url
            .replace('{payment}', parsedArgs.payment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::reconcile
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:103
* @route '/cooperative/operator/payments/{payment}/reconcile'
*/
reconcile.post = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reconcile.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::reconcile
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:103
* @route '/cooperative/operator/payments/{payment}/reconcile'
*/
const reconcileForm = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reconcile.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::reconcile
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:103
* @route '/cooperative/operator/payments/{payment}/reconcile'
*/
reconcileForm.post = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reconcile.url(args, options),
    method: 'post',
})

reconcile.form = reconcileForm

const payments = {
    reconcile: Object.assign(reconcile, reconcile),
}

export default payments