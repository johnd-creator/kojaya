import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\EmployeeTransferController::index
* @see app/Http/Controllers/EmployeeTransferController.php:16
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
* @see app/Http/Controllers/EmployeeTransferController.php:16
* @route '/employee-transfers'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EmployeeTransferController::index
* @see app/Http/Controllers/EmployeeTransferController.php:16
* @route '/employee-transfers'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EmployeeTransferController::index
* @see app/Http/Controllers/EmployeeTransferController.php:16
* @route '/employee-transfers'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EmployeeTransferController::create
* @see app/Http/Controllers/EmployeeTransferController.php:44
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
* @see app/Http/Controllers/EmployeeTransferController.php:44
* @route '/employee-transfers/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EmployeeTransferController::create
* @see app/Http/Controllers/EmployeeTransferController.php:44
* @route '/employee-transfers/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EmployeeTransferController::create
* @see app/Http/Controllers/EmployeeTransferController.php:44
* @route '/employee-transfers/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EmployeeTransferController::store
* @see app/Http/Controllers/EmployeeTransferController.php:57
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
* @see app/Http/Controllers/EmployeeTransferController.php:57
* @route '/employee-transfers'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EmployeeTransferController::store
* @see app/Http/Controllers/EmployeeTransferController.php:57
* @route '/employee-transfers'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EmployeeTransferController::show
* @see app/Http/Controllers/EmployeeTransferController.php:79
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
* @see app/Http/Controllers/EmployeeTransferController.php:79
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
* @see app/Http/Controllers/EmployeeTransferController.php:79
* @route '/employee-transfers/{employee_transfer}'
*/
show.get = (args: { employee_transfer: string | number } | [employee_transfer: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EmployeeTransferController::show
* @see app/Http/Controllers/EmployeeTransferController.php:79
* @route '/employee-transfers/{employee_transfer}'
*/
show.head = (args: { employee_transfer: string | number } | [employee_transfer: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EmployeeTransferController::approve
* @see app/Http/Controllers/EmployeeTransferController.php:90
* @route '/employee-transfers/{transfer}/approve'
*/
export const approve = (args: { transfer: string | number | { id: string | number } } | [transfer: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

approve.definition = {
    methods: ["post"],
    url: '/employee-transfers/{transfer}/approve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EmployeeTransferController::approve
* @see app/Http/Controllers/EmployeeTransferController.php:90
* @route '/employee-transfers/{transfer}/approve'
*/
approve.url = (args: { transfer: string | number | { id: string | number } } | [transfer: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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
* @see app/Http/Controllers/EmployeeTransferController.php:90
* @route '/employee-transfers/{transfer}/approve'
*/
approve.post = (args: { transfer: string | number | { id: string | number } } | [transfer: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EmployeeTransferController::reject
* @see app/Http/Controllers/EmployeeTransferController.php:109
* @route '/employee-transfers/{transfer}/reject'
*/
export const reject = (args: { transfer: string | number | { id: string | number } } | [transfer: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
})

reject.definition = {
    methods: ["post"],
    url: '/employee-transfers/{transfer}/reject',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EmployeeTransferController::reject
* @see app/Http/Controllers/EmployeeTransferController.php:109
* @route '/employee-transfers/{transfer}/reject'
*/
reject.url = (args: { transfer: string | number | { id: string | number } } | [transfer: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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
* @see app/Http/Controllers/EmployeeTransferController.php:109
* @route '/employee-transfers/{transfer}/reject'
*/
reject.post = (args: { transfer: string | number | { id: string | number } } | [transfer: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
})

const employeeTransfers = {
    index: Object.assign(index, index),
    create: Object.assign(create, create),
    store: Object.assign(store, store),
    show: Object.assign(show, show),
    approve: Object.assign(approve, approve),
    reject: Object.assign(reject, reject),
}

export default employeeTransfers