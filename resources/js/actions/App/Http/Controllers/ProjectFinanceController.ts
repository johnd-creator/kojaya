import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ProjectFinanceController::index
* @see app/Http/Controllers/ProjectFinanceController.php:20
* @route '/projects/{project}/financials'
*/
export const index = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/projects/{project}/financials',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ProjectFinanceController::index
* @see app/Http/Controllers/ProjectFinanceController.php:20
* @route '/projects/{project}/financials'
*/
index.url = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { project: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { project: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            project: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: typeof args.project === 'object'
        ? args.project.id
        : args.project,
    }

    return index.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectFinanceController::index
* @see app/Http/Controllers/ProjectFinanceController.php:20
* @route '/projects/{project}/financials'
*/
index.get = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectFinanceController::index
* @see app/Http/Controllers/ProjectFinanceController.php:20
* @route '/projects/{project}/financials'
*/
index.head = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ProjectFinanceController::index
* @see app/Http/Controllers/ProjectFinanceController.php:20
* @route '/projects/{project}/financials'
*/
const indexForm = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectFinanceController::index
* @see app/Http/Controllers/ProjectFinanceController.php:20
* @route '/projects/{project}/financials'
*/
indexForm.get = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectFinanceController::index
* @see app/Http/Controllers/ProjectFinanceController.php:20
* @route '/projects/{project}/financials'
*/
indexForm.head = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\ProjectFinanceController::summary
* @see app/Http/Controllers/ProjectFinanceController.php:140
* @route '/projects/{project}/financial-summary'
*/
export const summary = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: summary.url(args, options),
    method: 'get',
})

summary.definition = {
    methods: ["get","head"],
    url: '/projects/{project}/financial-summary',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ProjectFinanceController::summary
* @see app/Http/Controllers/ProjectFinanceController.php:140
* @route '/projects/{project}/financial-summary'
*/
summary.url = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { project: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { project: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            project: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: typeof args.project === 'object'
        ? args.project.id
        : args.project,
    }

    return summary.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectFinanceController::summary
* @see app/Http/Controllers/ProjectFinanceController.php:140
* @route '/projects/{project}/financial-summary'
*/
summary.get = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: summary.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectFinanceController::summary
* @see app/Http/Controllers/ProjectFinanceController.php:140
* @route '/projects/{project}/financial-summary'
*/
summary.head = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: summary.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ProjectFinanceController::summary
* @see app/Http/Controllers/ProjectFinanceController.php:140
* @route '/projects/{project}/financial-summary'
*/
const summaryForm = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: summary.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectFinanceController::summary
* @see app/Http/Controllers/ProjectFinanceController.php:140
* @route '/projects/{project}/financial-summary'
*/
summaryForm.get = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: summary.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectFinanceController::summary
* @see app/Http/Controllers/ProjectFinanceController.php:140
* @route '/projects/{project}/financial-summary'
*/
summaryForm.head = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: summary.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

summary.form = summaryForm

/**
* @see \App\Http\Controllers\ProjectFinanceController::budgetAnalysis
* @see app/Http/Controllers/ProjectFinanceController.php:153
* @route '/projects/{project}/budget-analysis'
*/
export const budgetAnalysis = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: budgetAnalysis.url(args, options),
    method: 'get',
})

budgetAnalysis.definition = {
    methods: ["get","head"],
    url: '/projects/{project}/budget-analysis',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ProjectFinanceController::budgetAnalysis
* @see app/Http/Controllers/ProjectFinanceController.php:153
* @route '/projects/{project}/budget-analysis'
*/
budgetAnalysis.url = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { project: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { project: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            project: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: typeof args.project === 'object'
        ? args.project.id
        : args.project,
    }

    return budgetAnalysis.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectFinanceController::budgetAnalysis
* @see app/Http/Controllers/ProjectFinanceController.php:153
* @route '/projects/{project}/budget-analysis'
*/
budgetAnalysis.get = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: budgetAnalysis.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectFinanceController::budgetAnalysis
* @see app/Http/Controllers/ProjectFinanceController.php:153
* @route '/projects/{project}/budget-analysis'
*/
budgetAnalysis.head = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: budgetAnalysis.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ProjectFinanceController::budgetAnalysis
* @see app/Http/Controllers/ProjectFinanceController.php:153
* @route '/projects/{project}/budget-analysis'
*/
const budgetAnalysisForm = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: budgetAnalysis.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectFinanceController::budgetAnalysis
* @see app/Http/Controllers/ProjectFinanceController.php:153
* @route '/projects/{project}/budget-analysis'
*/
budgetAnalysisForm.get = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: budgetAnalysis.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectFinanceController::budgetAnalysis
* @see app/Http/Controllers/ProjectFinanceController.php:153
* @route '/projects/{project}/budget-analysis'
*/
budgetAnalysisForm.head = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: budgetAnalysis.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

budgetAnalysis.form = budgetAnalysisForm

/**
* @see \App\Http\Controllers\ProjectFinanceController::transactions
* @see app/Http/Controllers/ProjectFinanceController.php:163
* @route '/projects/{project}/transactions'
*/
export const transactions = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: transactions.url(args, options),
    method: 'get',
})

transactions.definition = {
    methods: ["get","head"],
    url: '/projects/{project}/transactions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ProjectFinanceController::transactions
* @see app/Http/Controllers/ProjectFinanceController.php:163
* @route '/projects/{project}/transactions'
*/
transactions.url = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { project: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { project: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            project: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: typeof args.project === 'object'
        ? args.project.id
        : args.project,
    }

    return transactions.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectFinanceController::transactions
* @see app/Http/Controllers/ProjectFinanceController.php:163
* @route '/projects/{project}/transactions'
*/
transactions.get = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: transactions.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectFinanceController::transactions
* @see app/Http/Controllers/ProjectFinanceController.php:163
* @route '/projects/{project}/transactions'
*/
transactions.head = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: transactions.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ProjectFinanceController::transactions
* @see app/Http/Controllers/ProjectFinanceController.php:163
* @route '/projects/{project}/transactions'
*/
const transactionsForm = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: transactions.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectFinanceController::transactions
* @see app/Http/Controllers/ProjectFinanceController.php:163
* @route '/projects/{project}/transactions'
*/
transactionsForm.get = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: transactions.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectFinanceController::transactions
* @see app/Http/Controllers/ProjectFinanceController.php:163
* @route '/projects/{project}/transactions'
*/
transactionsForm.head = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: transactions.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

transactions.form = transactionsForm

const ProjectFinanceController = { index, summary, budgetAnalysis, transactions }

export default ProjectFinanceController