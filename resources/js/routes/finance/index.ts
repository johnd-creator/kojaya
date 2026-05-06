import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
import efaktur from './efaktur'
import bankBatches from './bank-batches'
import bankReconciliation from './bank-reconciliation'
import chartOfAccounts from './chart-of-accounts'
import journalEntries from './journal-entries'
/**
* @see \App\Http\Controllers\Accounting\FinancialStatementController::trialBalance
* @see app/Http/Controllers/Accounting/FinancialStatementController.php:13
* @route '/finance/trial-balance'
*/
export const trialBalance = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: trialBalance.url(options),
    method: 'get',
})

trialBalance.definition = {
    methods: ["get","head"],
    url: '/finance/trial-balance',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Accounting\FinancialStatementController::trialBalance
* @see app/Http/Controllers/Accounting/FinancialStatementController.php:13
* @route '/finance/trial-balance'
*/
trialBalance.url = (options?: RouteQueryOptions) => {
    return trialBalance.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Accounting\FinancialStatementController::trialBalance
* @see app/Http/Controllers/Accounting/FinancialStatementController.php:13
* @route '/finance/trial-balance'
*/
trialBalance.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: trialBalance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Accounting\FinancialStatementController::trialBalance
* @see app/Http/Controllers/Accounting/FinancialStatementController.php:13
* @route '/finance/trial-balance'
*/
trialBalance.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: trialBalance.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Accounting\FinancialStatementController::trialBalance
* @see app/Http/Controllers/Accounting/FinancialStatementController.php:13
* @route '/finance/trial-balance'
*/
const trialBalanceForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: trialBalance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Accounting\FinancialStatementController::trialBalance
* @see app/Http/Controllers/Accounting/FinancialStatementController.php:13
* @route '/finance/trial-balance'
*/
trialBalanceForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: trialBalance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Accounting\FinancialStatementController::trialBalance
* @see app/Http/Controllers/Accounting/FinancialStatementController.php:13
* @route '/finance/trial-balance'
*/
trialBalanceForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: trialBalance.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

trialBalance.form = trialBalanceForm

/**
* @see \App\Http\Controllers\Accounting\FinancialStatementController::balanceSheet
* @see app/Http/Controllers/Accounting/FinancialStatementController.php:26
* @route '/finance/balance-sheet'
*/
export const balanceSheet = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: balanceSheet.url(options),
    method: 'get',
})

balanceSheet.definition = {
    methods: ["get","head"],
    url: '/finance/balance-sheet',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Accounting\FinancialStatementController::balanceSheet
* @see app/Http/Controllers/Accounting/FinancialStatementController.php:26
* @route '/finance/balance-sheet'
*/
balanceSheet.url = (options?: RouteQueryOptions) => {
    return balanceSheet.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Accounting\FinancialStatementController::balanceSheet
* @see app/Http/Controllers/Accounting/FinancialStatementController.php:26
* @route '/finance/balance-sheet'
*/
balanceSheet.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: balanceSheet.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Accounting\FinancialStatementController::balanceSheet
* @see app/Http/Controllers/Accounting/FinancialStatementController.php:26
* @route '/finance/balance-sheet'
*/
balanceSheet.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: balanceSheet.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Accounting\FinancialStatementController::balanceSheet
* @see app/Http/Controllers/Accounting/FinancialStatementController.php:26
* @route '/finance/balance-sheet'
*/
const balanceSheetForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: balanceSheet.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Accounting\FinancialStatementController::balanceSheet
* @see app/Http/Controllers/Accounting/FinancialStatementController.php:26
* @route '/finance/balance-sheet'
*/
balanceSheetForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: balanceSheet.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Accounting\FinancialStatementController::balanceSheet
* @see app/Http/Controllers/Accounting/FinancialStatementController.php:26
* @route '/finance/balance-sheet'
*/
balanceSheetForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: balanceSheet.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

balanceSheet.form = balanceSheetForm

/**
* @see \App\Http\Controllers\Accounting\FinancialStatementController::incomeStatement
* @see app/Http/Controllers/Accounting/FinancialStatementController.php:39
* @route '/finance/income-statement'
*/
export const incomeStatement = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: incomeStatement.url(options),
    method: 'get',
})

incomeStatement.definition = {
    methods: ["get","head"],
    url: '/finance/income-statement',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Accounting\FinancialStatementController::incomeStatement
* @see app/Http/Controllers/Accounting/FinancialStatementController.php:39
* @route '/finance/income-statement'
*/
incomeStatement.url = (options?: RouteQueryOptions) => {
    return incomeStatement.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Accounting\FinancialStatementController::incomeStatement
* @see app/Http/Controllers/Accounting/FinancialStatementController.php:39
* @route '/finance/income-statement'
*/
incomeStatement.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: incomeStatement.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Accounting\FinancialStatementController::incomeStatement
* @see app/Http/Controllers/Accounting/FinancialStatementController.php:39
* @route '/finance/income-statement'
*/
incomeStatement.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: incomeStatement.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Accounting\FinancialStatementController::incomeStatement
* @see app/Http/Controllers/Accounting/FinancialStatementController.php:39
* @route '/finance/income-statement'
*/
const incomeStatementForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: incomeStatement.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Accounting\FinancialStatementController::incomeStatement
* @see app/Http/Controllers/Accounting/FinancialStatementController.php:39
* @route '/finance/income-statement'
*/
incomeStatementForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: incomeStatement.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Accounting\FinancialStatementController::incomeStatement
* @see app/Http/Controllers/Accounting/FinancialStatementController.php:39
* @route '/finance/income-statement'
*/
incomeStatementForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: incomeStatement.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

incomeStatement.form = incomeStatementForm

const finance = {
    efaktur: Object.assign(efaktur, efaktur),
    bankBatches: Object.assign(bankBatches, bankBatches),
    bankReconciliation: Object.assign(bankReconciliation, bankReconciliation),
    chartOfAccounts: Object.assign(chartOfAccounts, chartOfAccounts),
    journalEntries: Object.assign(journalEntries, journalEntries),
    trialBalance: Object.assign(trialBalance, trialBalance),
    balanceSheet: Object.assign(balanceSheet, balanceSheet),
    incomeStatement: Object.assign(incomeStatement, incomeStatement),
}

export default finance