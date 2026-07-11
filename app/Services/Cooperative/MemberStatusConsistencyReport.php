<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeMember;
use Illuminate\Database\Eloquent\Builder;

class MemberStatusConsistencyReport
{
    /** @var array<int, array{0: string, 1: string}> */
    private const VALID_PAIRS = [
        ['PENDING', CooperativeMember::VALIDATION_PENDING],
        ['PENDING', CooperativeMember::VALIDATION_PENDING_REVIEW],
        ['INACTIVE', CooperativeMember::VALIDATION_INACTIVE],
        ['INACTIVE', CooperativeMember::VALIDATION_REVISION],
        ['INACTIVE', CooperativeMember::VALIDATION_REJECTED],
        ['ACTIVE', CooperativeMember::VALIDATION_ACTIVE],
        ['RESIGNED', CooperativeMember::VALIDATION_RESIGNED],
    ];

    /** @return array<string, int> */
    public function counts(): array
    {
        $knownStatuses = $this->knownStatuses();
        $knownValidationStatuses = $this->knownValidationStatuses();

        return [
            'total' => CooperativeMember::query()->count(),
            'ACTIVE/ACTIVE' => CooperativeMember::query()->where('status', 'ACTIVE')->where('validation_status', CooperativeMember::VALIDATION_ACTIVE)->count(),
            'ACTIVE/null' => CooperativeMember::query()->where('status', 'ACTIVE')->whereNull('validation_status')->count(),
            'ACTIVE/non-active-validation' => CooperativeMember::query()->where('status', 'ACTIVE')->whereNotNull('validation_status')->where('validation_status', '!=', CooperativeMember::VALIDATION_ACTIVE)->count(),
            'non-active/ACTIVE' => CooperativeMember::query()->whereIn('status', ['PENDING', 'INACTIVE', 'RESIGNED'])->where('validation_status', CooperativeMember::VALIDATION_ACTIVE)->count(),
            'unknown status' => CooperativeMember::query()->whereNotIn('status', $knownStatuses)->count(),
            'unknown validation_status' => CooperativeMember::query()->whereNotNull('validation_status')->whereNotIn('validation_status', $knownValidationStatuses)->count(),
            'manual-review' => $this->manualReviewQuery()->count(),
        ];
    }

    /** @return Builder<CooperativeMember> */
    public function deterministicRepairs(): Builder
    {
        return CooperativeMember::query()->where(function (Builder $query): void {
            $query->where(function (Builder $query): void {
                $query->where('status', 'ACTIVE')->whereNull('validation_status');
            })->orWhere(function (Builder $query): void {
                $query->where('status', 'INACTIVE')->where('validation_status', CooperativeMember::VALIDATION_ACTIVE);
            })->orWhere(function (Builder $query): void {
                $query->where('status', 'RESIGNED')->where('validation_status', CooperativeMember::VALIDATION_ACTIVE);
            });
        });
    }

    /** @return Builder<CooperativeMember> */
    public function manualReviewQuery(): Builder
    {
        $validPairs = self::VALID_PAIRS;
        $knownStatuses = $this->knownStatuses();
        $knownValidationStatuses = $this->knownValidationStatuses();

        return CooperativeMember::query()->where(function (Builder $query) use ($validPairs, $knownStatuses, $knownValidationStatuses): void {
            $query->where(function (Builder $query) use ($knownStatuses): void {
                $query->whereNotIn('status', $knownStatuses)
                    ->orWhere(function (Builder $query): void {
                        $query->whereNull('validation_status')->where('status', '!=', 'ACTIVE');
                    });
            })->orWhere(function (Builder $query) use ($validPairs, $knownValidationStatuses): void {
                $query->whereNotNull('validation_status')
                    ->whereNotIn('validation_status', $knownValidationStatuses)
                    ->orWhere(function (Builder $query) use ($validPairs): void {
                        $query->whereNotNull('validation_status')->where(function (Builder $query) use ($validPairs): void {
                            foreach ($validPairs as [$status, $validationStatus]) {
                                $query->whereNot(function (Builder $pair) use ($status, $validationStatus): void {
                                    $pair->where('status', $status)->where('validation_status', $validationStatus);
                                });
                            }
                        });
                    });
            });
        })->whereNotIn('id', $this->deterministicRepairs()->select('id'));
    }

    /** @return array<int, string> */
    public function knownStatuses(): array
    {
        return ['PENDING', 'ACTIVE', 'INACTIVE', 'RESIGNED'];
    }

    /** @return array<int, string> */
    public function knownValidationStatuses(): array
    {
        return [
            CooperativeMember::VALIDATION_PENDING,
            CooperativeMember::VALIDATION_PENDING_REVIEW,
            CooperativeMember::VALIDATION_ACTIVE,
            CooperativeMember::VALIDATION_INACTIVE,
            CooperativeMember::VALIDATION_REJECTED,
            CooperativeMember::VALIDATION_REVISION,
            CooperativeMember::VALIDATION_RESIGNED,
        ];
    }
}
