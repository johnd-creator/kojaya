import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
import efaktur from './efaktur'
/**
* @see \App\Http\Controllers\InvoiceController::index
* @see app/Http/Controllers/InvoiceController.php:18
* @route '/invoices'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/invoices',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvoiceController::index
* @see app/Http/Controllers/InvoiceController.php:18
* @route '/invoices'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvoiceController::index
* @see app/Http/Controllers/InvoiceController.php:18
* @route '/invoices'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvoiceController::index
* @see app/Http/Controllers/InvoiceController.php:18
* @route '/invoices'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvoiceController::index
* @see app/Http/Controllers/InvoiceController.php:18
* @route '/invoices'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvoiceController::index
* @see app/Http/Controllers/InvoiceController.php:18
* @route '/invoices'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvoiceController::index
* @see app/Http/Controllers/InvoiceController.php:18
* @route '/invoices'
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
* @see \App\Http\Controllers\InvoiceController::create
* @see app/Http/Controllers/InvoiceController.php:46
* @route '/invoices/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/invoices/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvoiceController::create
* @see app/Http/Controllers/InvoiceController.php:46
* @route '/invoices/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvoiceController::create
* @see app/Http/Controllers/InvoiceController.php:46
* @route '/invoices/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvoiceController::create
* @see app/Http/Controllers/InvoiceController.php:46
* @route '/invoices/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvoiceController::create
* @see app/Http/Controllers/InvoiceController.php:46
* @route '/invoices/create'
*/
const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvoiceController::create
* @see app/Http/Controllers/InvoiceController.php:46
* @route '/invoices/create'
*/
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvoiceController::create
* @see app/Http/Controllers/InvoiceController.php:46
* @route '/invoices/create'
*/
createForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

create.form = createForm

/**
* @see \App\Http\Controllers\InvoiceController::store
* @see app/Http/Controllers/InvoiceController.php:58
* @route '/invoices'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/invoices',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvoiceController::store
* @see app/Http/Controllers/InvoiceController.php:58
* @route '/invoices'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvoiceController::store
* @see app/Http/Controllers/InvoiceController.php:58
* @route '/invoices'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvoiceController::store
* @see app/Http/Controllers/InvoiceController.php:58
* @route '/invoices'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvoiceController::store
* @see app/Http/Controllers/InvoiceController.php:58
* @route '/invoices'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\InvoiceController::show
* @see app/Http/Controllers/InvoiceController.php:77
* @route '/invoices/{invoice}'
*/
export const show = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/invoices/{invoice}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvoiceController::show
* @see app/Http/Controllers/InvoiceController.php:77
* @route '/invoices/{invoice}'
*/
show.url = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { invoice: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { invoice: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            invoice: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        invoice: typeof args.invoice === 'object'
        ? args.invoice.id
        : args.invoice,
    }

    return show.definition.url
            .replace('{invoice}', parsedArgs.invoice.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvoiceController::show
* @see app/Http/Controllers/InvoiceController.php:77
* @route '/invoices/{invoice}'
*/
show.get = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvoiceController::show
* @see app/Http/Controllers/InvoiceController.php:77
* @route '/invoices/{invoice}'
*/
show.head = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvoiceController::show
* @see app/Http/Controllers/InvoiceController.php:77
* @route '/invoices/{invoice}'
*/
const showForm = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvoiceController::show
* @see app/Http/Controllers/InvoiceController.php:77
* @route '/invoices/{invoice}'
*/
showForm.get = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvoiceController::show
* @see app/Http/Controllers/InvoiceController.php:77
* @route '/invoices/{invoice}'
*/
showForm.head = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\InvoiceController::edit
* @see app/Http/Controllers/InvoiceController.php:104
* @route '/invoices/{invoice}/edit'
*/
export const edit = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/invoices/{invoice}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvoiceController::edit
* @see app/Http/Controllers/InvoiceController.php:104
* @route '/invoices/{invoice}/edit'
*/
edit.url = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { invoice: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { invoice: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            invoice: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        invoice: typeof args.invoice === 'object'
        ? args.invoice.id
        : args.invoice,
    }

    return edit.definition.url
            .replace('{invoice}', parsedArgs.invoice.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvoiceController::edit
* @see app/Http/Controllers/InvoiceController.php:104
* @route '/invoices/{invoice}/edit'
*/
edit.get = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvoiceController::edit
* @see app/Http/Controllers/InvoiceController.php:104
* @route '/invoices/{invoice}/edit'
*/
edit.head = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvoiceController::edit
* @see app/Http/Controllers/InvoiceController.php:104
* @route '/invoices/{invoice}/edit'
*/
const editForm = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvoiceController::edit
* @see app/Http/Controllers/InvoiceController.php:104
* @route '/invoices/{invoice}/edit'
*/
editForm.get = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvoiceController::edit
* @see app/Http/Controllers/InvoiceController.php:104
* @route '/invoices/{invoice}/edit'
*/
editForm.head = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

edit.form = editForm

/**
* @see \App\Http\Controllers\InvoiceController::update
* @see app/Http/Controllers/InvoiceController.php:118
* @route '/invoices/{invoice}'
*/
export const update = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put","patch"],
    url: '/invoices/{invoice}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\InvoiceController::update
* @see app/Http/Controllers/InvoiceController.php:118
* @route '/invoices/{invoice}'
*/
update.url = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { invoice: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { invoice: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            invoice: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        invoice: typeof args.invoice === 'object'
        ? args.invoice.id
        : args.invoice,
    }

    return update.definition.url
            .replace('{invoice}', parsedArgs.invoice.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvoiceController::update
* @see app/Http/Controllers/InvoiceController.php:118
* @route '/invoices/{invoice}'
*/
update.put = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\InvoiceController::update
* @see app/Http/Controllers/InvoiceController.php:118
* @route '/invoices/{invoice}'
*/
update.patch = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\InvoiceController::update
* @see app/Http/Controllers/InvoiceController.php:118
* @route '/invoices/{invoice}'
*/
const updateForm = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvoiceController::update
* @see app/Http/Controllers/InvoiceController.php:118
* @route '/invoices/{invoice}'
*/
updateForm.put = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvoiceController::update
* @see app/Http/Controllers/InvoiceController.php:118
* @route '/invoices/{invoice}'
*/
updateForm.patch = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

/**
* @see \App\Http\Controllers\InvoiceController::destroy
* @see app/Http/Controllers/InvoiceController.php:145
* @route '/invoices/{invoice}'
*/
export const destroy = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/invoices/{invoice}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\InvoiceController::destroy
* @see app/Http/Controllers/InvoiceController.php:145
* @route '/invoices/{invoice}'
*/
destroy.url = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { invoice: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { invoice: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            invoice: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        invoice: typeof args.invoice === 'object'
        ? args.invoice.id
        : args.invoice,
    }

    return destroy.definition.url
            .replace('{invoice}', parsedArgs.invoice.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvoiceController::destroy
* @see app/Http/Controllers/InvoiceController.php:145
* @route '/invoices/{invoice}'
*/
destroy.delete = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\InvoiceController::destroy
* @see app/Http/Controllers/InvoiceController.php:145
* @route '/invoices/{invoice}'
*/
const destroyForm = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvoiceController::destroy
* @see app/Http/Controllers/InvoiceController.php:145
* @route '/invoices/{invoice}'
*/
destroyForm.delete = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

/**
* @see \App\Http\Controllers\InvoiceController::exportEfakturCsv
* @see app/Http/Controllers/InvoiceController.php:88
* @route '/invoices/{invoice}/export-efaktur-csv'
*/
export const exportEfakturCsv = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportEfakturCsv.url(args, options),
    method: 'get',
})

exportEfakturCsv.definition = {
    methods: ["get","head"],
    url: '/invoices/{invoice}/export-efaktur-csv',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvoiceController::exportEfakturCsv
* @see app/Http/Controllers/InvoiceController.php:88
* @route '/invoices/{invoice}/export-efaktur-csv'
*/
exportEfakturCsv.url = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { invoice: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { invoice: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            invoice: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        invoice: typeof args.invoice === 'object'
        ? args.invoice.id
        : args.invoice,
    }

    return exportEfakturCsv.definition.url
            .replace('{invoice}', parsedArgs.invoice.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvoiceController::exportEfakturCsv
* @see app/Http/Controllers/InvoiceController.php:88
* @route '/invoices/{invoice}/export-efaktur-csv'
*/
exportEfakturCsv.get = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportEfakturCsv.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvoiceController::exportEfakturCsv
* @see app/Http/Controllers/InvoiceController.php:88
* @route '/invoices/{invoice}/export-efaktur-csv'
*/
exportEfakturCsv.head = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: exportEfakturCsv.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvoiceController::exportEfakturCsv
* @see app/Http/Controllers/InvoiceController.php:88
* @route '/invoices/{invoice}/export-efaktur-csv'
*/
const exportEfakturCsvForm = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: exportEfakturCsv.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvoiceController::exportEfakturCsv
* @see app/Http/Controllers/InvoiceController.php:88
* @route '/invoices/{invoice}/export-efaktur-csv'
*/
exportEfakturCsvForm.get = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: exportEfakturCsv.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvoiceController::exportEfakturCsv
* @see app/Http/Controllers/InvoiceController.php:88
* @route '/invoices/{invoice}/export-efaktur-csv'
*/
exportEfakturCsvForm.head = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: exportEfakturCsv.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

exportEfakturCsv.form = exportEfakturCsvForm

/**
* @see \App\Http\Controllers\InvoiceController::submitForApproval
* @see app/Http/Controllers/InvoiceController.php:162
* @route '/invoices/{invoice}/submit-for-approval'
*/
export const submitForApproval = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submitForApproval.url(args, options),
    method: 'post',
})

submitForApproval.definition = {
    methods: ["post"],
    url: '/invoices/{invoice}/submit-for-approval',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvoiceController::submitForApproval
* @see app/Http/Controllers/InvoiceController.php:162
* @route '/invoices/{invoice}/submit-for-approval'
*/
submitForApproval.url = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { invoice: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { invoice: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            invoice: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        invoice: typeof args.invoice === 'object'
        ? args.invoice.id
        : args.invoice,
    }

    return submitForApproval.definition.url
            .replace('{invoice}', parsedArgs.invoice.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvoiceController::submitForApproval
* @see app/Http/Controllers/InvoiceController.php:162
* @route '/invoices/{invoice}/submit-for-approval'
*/
submitForApproval.post = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submitForApproval.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvoiceController::submitForApproval
* @see app/Http/Controllers/InvoiceController.php:162
* @route '/invoices/{invoice}/submit-for-approval'
*/
const submitForApprovalForm = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: submitForApproval.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvoiceController::submitForApproval
* @see app/Http/Controllers/InvoiceController.php:162
* @route '/invoices/{invoice}/submit-for-approval'
*/
submitForApprovalForm.post = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: submitForApproval.url(args, options),
    method: 'post',
})

submitForApproval.form = submitForApprovalForm

/**
* @see \App\Http\Controllers\InvoiceController::approve
* @see app/Http/Controllers/InvoiceController.php:179
* @route '/invoices/{invoice}/approve'
*/
export const approve = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

approve.definition = {
    methods: ["post"],
    url: '/invoices/{invoice}/approve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvoiceController::approve
* @see app/Http/Controllers/InvoiceController.php:179
* @route '/invoices/{invoice}/approve'
*/
approve.url = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { invoice: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { invoice: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            invoice: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        invoice: typeof args.invoice === 'object'
        ? args.invoice.id
        : args.invoice,
    }

    return approve.definition.url
            .replace('{invoice}', parsedArgs.invoice.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvoiceController::approve
* @see app/Http/Controllers/InvoiceController.php:179
* @route '/invoices/{invoice}/approve'
*/
approve.post = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvoiceController::approve
* @see app/Http/Controllers/InvoiceController.php:179
* @route '/invoices/{invoice}/approve'
*/
const approveForm = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvoiceController::approve
* @see app/Http/Controllers/InvoiceController.php:179
* @route '/invoices/{invoice}/approve'
*/
approveForm.post = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
})

approve.form = approveForm

/**
* @see \App\Http\Controllers\InvoiceController::reject
* @see app/Http/Controllers/InvoiceController.php:198
* @route '/invoices/{invoice}/reject'
*/
export const reject = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
})

reject.definition = {
    methods: ["post"],
    url: '/invoices/{invoice}/reject',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvoiceController::reject
* @see app/Http/Controllers/InvoiceController.php:198
* @route '/invoices/{invoice}/reject'
*/
reject.url = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { invoice: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { invoice: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            invoice: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        invoice: typeof args.invoice === 'object'
        ? args.invoice.id
        : args.invoice,
    }

    return reject.definition.url
            .replace('{invoice}', parsedArgs.invoice.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvoiceController::reject
* @see app/Http/Controllers/InvoiceController.php:198
* @route '/invoices/{invoice}/reject'
*/
reject.post = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvoiceController::reject
* @see app/Http/Controllers/InvoiceController.php:198
* @route '/invoices/{invoice}/reject'
*/
const rejectForm = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvoiceController::reject
* @see app/Http/Controllers/InvoiceController.php:198
* @route '/invoices/{invoice}/reject'
*/
rejectForm.post = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(args, options),
    method: 'post',
})

reject.form = rejectForm

/**
* @see \App\Http\Controllers\InvoiceController::markAsPaid
* @see app/Http/Controllers/InvoiceController.php:217
* @route '/invoices/{invoice}/mark-as-paid'
*/
export const markAsPaid = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markAsPaid.url(args, options),
    method: 'post',
})

markAsPaid.definition = {
    methods: ["post"],
    url: '/invoices/{invoice}/mark-as-paid',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvoiceController::markAsPaid
* @see app/Http/Controllers/InvoiceController.php:217
* @route '/invoices/{invoice}/mark-as-paid'
*/
markAsPaid.url = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { invoice: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { invoice: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            invoice: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        invoice: typeof args.invoice === 'object'
        ? args.invoice.id
        : args.invoice,
    }

    return markAsPaid.definition.url
            .replace('{invoice}', parsedArgs.invoice.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvoiceController::markAsPaid
* @see app/Http/Controllers/InvoiceController.php:217
* @route '/invoices/{invoice}/mark-as-paid'
*/
markAsPaid.post = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markAsPaid.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvoiceController::markAsPaid
* @see app/Http/Controllers/InvoiceController.php:217
* @route '/invoices/{invoice}/mark-as-paid'
*/
const markAsPaidForm = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: markAsPaid.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvoiceController::markAsPaid
* @see app/Http/Controllers/InvoiceController.php:217
* @route '/invoices/{invoice}/mark-as-paid'
*/
markAsPaidForm.post = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: markAsPaid.url(args, options),
    method: 'post',
})

markAsPaid.form = markAsPaidForm

const invoices = {
    index: Object.assign(index, index),
    create: Object.assign(create, create),
    store: Object.assign(store, store),
    show: Object.assign(show, show),
    edit: Object.assign(edit, edit),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
    exportEfakturCsv: Object.assign(exportEfakturCsv, exportEfakturCsv),
    efaktur: Object.assign(efaktur, efaktur),
    submitForApproval: Object.assign(submitForApproval, submitForApproval),
    approve: Object.assign(approve, approve),
    reject: Object.assign(reject, reject),
    markAsPaid: Object.assign(markAsPaid, markAsPaid),
}

export default invoices