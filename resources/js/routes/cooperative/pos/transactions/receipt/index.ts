import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\PosTransactionReceiptController::pdf
* @see app/Http/Controllers/Cooperative/PosTransactionReceiptController.php:21
* @route '/cooperative/pos/transactions/{transaction}/receipt.pdf'
*/
export const pdf = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: pdf.url(args, options),
    method: 'get',
})

pdf.definition = {
    methods: ["get","head"],
    url: '/cooperative/pos/transactions/{transaction}/receipt.pdf',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\PosTransactionReceiptController::pdf
* @see app/Http/Controllers/Cooperative/PosTransactionReceiptController.php:21
* @route '/cooperative/pos/transactions/{transaction}/receipt.pdf'
*/
pdf.url = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { transaction: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { transaction: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            transaction: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        transaction: typeof args.transaction === 'object'
        ? args.transaction.id
        : args.transaction,
    }

    return pdf.definition.url
            .replace('{transaction}', parsedArgs.transaction.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosTransactionReceiptController::pdf
* @see app/Http/Controllers/Cooperative/PosTransactionReceiptController.php:21
* @route '/cooperative/pos/transactions/{transaction}/receipt.pdf'
*/
pdf.get = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: pdf.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosTransactionReceiptController::pdf
* @see app/Http/Controllers/Cooperative/PosTransactionReceiptController.php:21
* @route '/cooperative/pos/transactions/{transaction}/receipt.pdf'
*/
pdf.head = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: pdf.url(args, options),
    method: 'head',
})

const receipt = {
    pdf: Object.assign(pdf, pdf),
}

export default receipt