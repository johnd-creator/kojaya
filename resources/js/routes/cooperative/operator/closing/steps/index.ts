import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::complete
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:79
* @route '/cooperative/operator/closing/{period}/steps'
*/
export const complete = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: complete.url(args, options),
    method: 'post',
})

complete.definition = {
    methods: ["post"],
    url: '/cooperative/operator/closing/{period}/steps',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::complete
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:79
* @route '/cooperative/operator/closing/{period}/steps'
*/
complete.url = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { period: args }
    }

    if (Array.isArray(args)) {
        args = {
            period: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        period: args.period,
    }

    return complete.definition.url
            .replace('{period}', parsedArgs.period.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::complete
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:79
* @route '/cooperative/operator/closing/{period}/steps'
*/
complete.post = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: complete.url(args, options),
    method: 'post',
})

const steps = {
    complete: Object.assign(complete, complete),
}

export default steps