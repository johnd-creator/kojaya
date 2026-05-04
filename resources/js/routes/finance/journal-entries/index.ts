import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
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
* @see \App\Http\Controllers\Accounting\JournalEntryController::index
* @see app/Http/Controllers/Accounting/JournalEntryController.php:17
* @route '/finance/journal-entries'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Accounting\JournalEntryController::index
* @see app/Http/Controllers/Accounting/JournalEntryController.php:17
* @route '/finance/journal-entries'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Accounting\JournalEntryController::index
* @see app/Http/Controllers/Accounting/JournalEntryController.php:17
* @route '/finance/journal-entries'
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
* @see \App\Http\Controllers\Accounting\JournalEntryController::store
* @see app/Http/Controllers/Accounting/JournalEntryController.php:32
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
* @see app/Http/Controllers/Accounting/JournalEntryController.php:32
* @route '/finance/journal-entries'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Accounting\JournalEntryController::store
* @see app/Http/Controllers/Accounting/JournalEntryController.php:32
* @route '/finance/journal-entries'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Accounting\JournalEntryController::store
* @see app/Http/Controllers/Accounting/JournalEntryController.php:32
* @route '/finance/journal-entries'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Accounting\JournalEntryController::store
* @see app/Http/Controllers/Accounting/JournalEntryController.php:32
* @route '/finance/journal-entries'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

const journalEntries = {
    index: Object.assign(index, index),
    store: Object.assign(store, store),
}

export default journalEntries