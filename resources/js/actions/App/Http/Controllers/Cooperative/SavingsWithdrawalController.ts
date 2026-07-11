import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\SavingsWithdrawalController::index
* @see app/Http/Controllers/Cooperative/SavingsWithdrawalController.php:20
* @route '/cooperative/savings/withdrawals'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cooperative/savings/withdrawals',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\SavingsWithdrawalController::index
* @see app/Http/Controllers/Cooperative/SavingsWithdrawalController.php:20
* @route '/cooperative/savings/withdrawals'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\SavingsWithdrawalController::index
* @see app/Http/Controllers/Cooperative/SavingsWithdrawalController.php:20
* @route '/cooperative/savings/withdrawals'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\SavingsWithdrawalController::index
* @see app/Http/Controllers/Cooperative/SavingsWithdrawalController.php:20
* @route '/cooperative/savings/withdrawals'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\SavingsWithdrawalController::process
* @see app/Http/Controllers/Cooperative/SavingsWithdrawalController.php:36
* @route '/cooperative/savings/withdrawals/{withdrawal}/process'
*/
export const process = (args: { withdrawal: number | { id: number } } | [withdrawal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: process.url(args, options),
    method: 'post',
})

process.definition = {
    methods: ["post"],
    url: '/cooperative/savings/withdrawals/{withdrawal}/process',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\SavingsWithdrawalController::process
* @see app/Http/Controllers/Cooperative/SavingsWithdrawalController.php:36
* @route '/cooperative/savings/withdrawals/{withdrawal}/process'
*/
process.url = (args: { withdrawal: number | { id: number } } | [withdrawal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { withdrawal: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { withdrawal: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            withdrawal: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        withdrawal: typeof args.withdrawal === 'object'
        ? args.withdrawal.id
        : args.withdrawal,
    }

    return process.definition.url
            .replace('{withdrawal}', parsedArgs.withdrawal.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\SavingsWithdrawalController::process
* @see app/Http/Controllers/Cooperative/SavingsWithdrawalController.php:36
* @route '/cooperative/savings/withdrawals/{withdrawal}/process'
*/
process.post = (args: { withdrawal: number | { id: number } } | [withdrawal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: process.url(args, options),
    method: 'post',
})

const SavingsWithdrawalController = { index, process }

export default SavingsWithdrawalController