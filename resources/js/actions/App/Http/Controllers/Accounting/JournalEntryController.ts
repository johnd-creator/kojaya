import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Accounting\JournalEntryController::index
* @see app/Http/Controllers/Accounting/JournalEntryController.php:17
* @route '/finance/journal-entries'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/finance/journal-entries',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Accounting\JournalEntryController::index
* @see app/Http/Controllers/Accounting/JournalEntryController.php:17
* @route '/finance/journal-entries'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Accounting\JournalEntryController::index
* @see app/Http/Controllers/Accounting/JournalEntryController.php:17
* @route '/finance/journal-entries'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Accounting\JournalEntryController::index
* @see app/Http/Controllers/Accounting/JournalEntryController.php:17
* @route '/finance/journal-entries'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Accounting\JournalEntryController::store
* @see app/Http/Controllers/Accounting/JournalEntryController.php:34
* @route '/finance/journal-entries'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/finance/journal-entries',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Accounting\JournalEntryController::store
* @see app/Http/Controllers/Accounting/JournalEntryController.php:34
* @route '/finance/journal-entries'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Accounting\JournalEntryController::store
* @see app/Http/Controllers/Accounting/JournalEntryController.php:34
* @route '/finance/journal-entries'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

const JournalEntryController = { index, store }

export default JournalEntryController