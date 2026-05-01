import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\EmployeeTransferController::index
* @see app/Http/Controllers/EmployeeTransferController.php:14
* @route '/employee-transfers'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/employee-transfers',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EmployeeTransferController::index
* @see app/Http/Controllers/EmployeeTransferController.php:14
* @route '/employee-transfers'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EmployeeTransferController::index
* @see app/Http/Controllers/EmployeeTransferController.php:14
* @route '/employee-transfers'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EmployeeTransferController::index
* @see app/Http/Controllers/EmployeeTransferController.php:14
* @route '/employee-transfers'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EmployeeTransferController::index
* @see app/Http/Controllers/EmployeeTransferController.php:14
* @route '/employee-transfers'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EmployeeTransferController::index
* @see app/Http/Controllers/EmployeeTransferController.php:14
* @route '/employee-transfers'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EmployeeTransferController::index
* @see app/Http/Controllers/EmployeeTransferController.php:14
* @route '/employee-transfers'
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
* @see \App\Http\Controllers\EmployeeTransferController::create
* @see app/Http/Controllers/EmployeeTransferController.php:40
* @route '/employee-transfers/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/employee-transfers/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EmployeeTransferController::create
* @see app/Http/Controllers/EmployeeTransferController.php:40
* @route '/employee-transfers/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EmployeeTransferController::create
* @see app/Http/Controllers/EmployeeTransferController.php:40
* @route '/employee-transfers/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EmployeeTransferController::create
* @see app/Http/Controllers/EmployeeTransferController.php:40
* @route '/employee-transfers/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EmployeeTransferController::create
* @see app/Http/Controllers/EmployeeTransferController.php:40
* @route '/employee-transfers/create'
*/
const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EmployeeTransferController::create
* @see app/Http/Controllers/EmployeeTransferController.php:40
* @route '/employee-transfers/create'
*/
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EmployeeTransferController::create
* @see app/Http/Controllers/EmployeeTransferController.php:40
* @route '/employee-transfers/create'
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
* @see \App\Http\Controllers\EmployeeTransferController::store
* @see app/Http/Controllers/EmployeeTransferController.php:51
* @route '/employee-transfers'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/employee-transfers',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EmployeeTransferController::store
* @see app/Http/Controllers/EmployeeTransferController.php:51
* @route '/employee-transfers'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EmployeeTransferController::store
* @see app/Http/Controllers/EmployeeTransferController.php:51
* @route '/employee-transfers'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EmployeeTransferController::store
* @see app/Http/Controllers/EmployeeTransferController.php:51
* @route '/employee-transfers'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EmployeeTransferController::store
* @see app/Http/Controllers/EmployeeTransferController.php:51
* @route '/employee-transfers'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\EmployeeTransferController::show
* @see app/Http/Controllers/EmployeeTransferController.php:76
* @route '/employee-transfers/{employee_transfer}'
*/
export const show = (args: { employee_transfer: string | number } | [employee_transfer: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/employee-transfers/{employee_transfer}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EmployeeTransferController::show
* @see app/Http/Controllers/EmployeeTransferController.php:76
* @route '/employee-transfers/{employee_transfer}'
*/
show.url = (args: { employee_transfer: string | number } | [employee_transfer: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { employee_transfer: args }
    }

    if (Array.isArray(args)) {
        args = {
            employee_transfer: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        employee_transfer: args.employee_transfer,
    }

    return show.definition.url
            .replace('{employee_transfer}', parsedArgs.employee_transfer.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EmployeeTransferController::show
* @see app/Http/Controllers/EmployeeTransferController.php:76
* @route '/employee-transfers/{employee_transfer}'
*/
show.get = (args: { employee_transfer: string | number } | [employee_transfer: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EmployeeTransferController::show
* @see app/Http/Controllers/EmployeeTransferController.php:76
* @route '/employee-transfers/{employee_transfer}'
*/
show.head = (args: { employee_transfer: string | number } | [employee_transfer: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EmployeeTransferController::show
* @see app/Http/Controllers/EmployeeTransferController.php:76
* @route '/employee-transfers/{employee_transfer}'
*/
const showForm = (args: { employee_transfer: string | number } | [employee_transfer: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EmployeeTransferController::show
* @see app/Http/Controllers/EmployeeTransferController.php:76
* @route '/employee-transfers/{employee_transfer}'
*/
showForm.get = (args: { employee_transfer: string | number } | [employee_transfer: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EmployeeTransferController::show
* @see app/Http/Controllers/EmployeeTransferController.php:76
* @route '/employee-transfers/{employee_transfer}'
*/
showForm.head = (args: { employee_transfer: string | number } | [employee_transfer: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\EmployeeTransferController::approve
* @see app/Http/Controllers/EmployeeTransferController.php:85
* @route '/employee-transfers/{transfer}/approve'
*/
export const approve = (args: { transfer: number | { id: number } } | [transfer: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

approve.definition = {
    methods: ["post"],
    url: '/employee-transfers/{transfer}/approve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EmployeeTransferController::approve
* @see app/Http/Controllers/EmployeeTransferController.php:85
* @route '/employee-transfers/{transfer}/approve'
*/
approve.url = (args: { transfer: number | { id: number } } | [transfer: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { transfer: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { transfer: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            transfer: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        transfer: typeof args.transfer === 'object'
        ? args.transfer.id
        : args.transfer,
    }

    return approve.definition.url
            .replace('{transfer}', parsedArgs.transfer.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EmployeeTransferController::approve
* @see app/Http/Controllers/EmployeeTransferController.php:85
* @route '/employee-transfers/{transfer}/approve'
*/
approve.post = (args: { transfer: number | { id: number } } | [transfer: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EmployeeTransferController::approve
* @see app/Http/Controllers/EmployeeTransferController.php:85
* @route '/employee-transfers/{transfer}/approve'
*/
const approveForm = (args: { transfer: number | { id: number } } | [transfer: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EmployeeTransferController::approve
* @see app/Http/Controllers/EmployeeTransferController.php:85
* @route '/employee-transfers/{transfer}/approve'
*/
approveForm.post = (args: { transfer: number | { id: number } } | [transfer: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
})

approve.form = approveForm

/**
* @see \App\Http\Controllers\EmployeeTransferController::reject
* @see app/Http/Controllers/EmployeeTransferController.php:104
* @route '/employee-transfers/{transfer}/reject'
*/
export const reject = (args: { transfer: number | { id: number } } | [transfer: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
})

reject.definition = {
    methods: ["post"],
    url: '/employee-transfers/{transfer}/reject',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EmployeeTransferController::reject
* @see app/Http/Controllers/EmployeeTransferController.php:104
* @route '/employee-transfers/{transfer}/reject'
*/
reject.url = (args: { transfer: number | { id: number } } | [transfer: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { transfer: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { transfer: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            transfer: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        transfer: typeof args.transfer === 'object'
        ? args.transfer.id
        : args.transfer,
    }

    return reject.definition.url
            .replace('{transfer}', parsedArgs.transfer.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EmployeeTransferController::reject
* @see app/Http/Controllers/EmployeeTransferController.php:104
* @route '/employee-transfers/{transfer}/reject'
*/
reject.post = (args: { transfer: number | { id: number } } | [transfer: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EmployeeTransferController::reject
* @see app/Http/Controllers/EmployeeTransferController.php:104
* @route '/employee-transfers/{transfer}/reject'
*/
const rejectForm = (args: { transfer: number | { id: number } } | [transfer: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EmployeeTransferController::reject
* @see app/Http/Controllers/EmployeeTransferController.php:104
* @route '/employee-transfers/{transfer}/reject'
*/
rejectForm.post = (args: { transfer: number | { id: number } } | [transfer: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(args, options),
    method: 'post',
})

reject.form = rejectForm

const EmployeeTransferController = { index, create, store, show, approve, reject }

export default EmployeeTransferController