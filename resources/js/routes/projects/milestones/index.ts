import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\ProjectMilestoneController::updateProgress
* @see app/Http/Controllers/ProjectMilestoneController.php:49
* @route '/projects/{project}/milestones/{milestone}/progress'
*/
export const updateProgress = (args: { project: string | { id: string }, milestone: string | { id: string } } | [project: string | { id: string }, milestone: string | { id: string } ], options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: updateProgress.url(args, options),
    method: 'patch',
})

updateProgress.definition = {
    methods: ["patch"],
    url: '/projects/{project}/milestones/{milestone}/progress',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ProjectMilestoneController::updateProgress
* @see app/Http/Controllers/ProjectMilestoneController.php:49
* @route '/projects/{project}/milestones/{milestone}/progress'
*/
updateProgress.url = (args: { project: string | { id: string }, milestone: string | { id: string } } | [project: string | { id: string }, milestone: string | { id: string } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            project: args[0],
            milestone: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: typeof args.project === 'object'
        ? args.project.id
        : args.project,
        milestone: typeof args.milestone === 'object'
        ? args.milestone.id
        : args.milestone,
    }

    return updateProgress.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace('{milestone}', parsedArgs.milestone.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectMilestoneController::updateProgress
* @see app/Http/Controllers/ProjectMilestoneController.php:49
* @route '/projects/{project}/milestones/{milestone}/progress'
*/
updateProgress.patch = (args: { project: string | { id: string }, milestone: string | { id: string } } | [project: string | { id: string }, milestone: string | { id: string } ], options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: updateProgress.url(args, options),
    method: 'patch',
})

const milestones = {
    updateProgress: Object.assign(updateProgress, updateProgress),
}

export default milestones