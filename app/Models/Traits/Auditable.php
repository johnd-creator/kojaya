<?php

namespace App\Models\Traits;

use App\Models\AuditLog;
use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            self::logModelChange('CREATE', $model);
        });

        static::updated(function (Model $model) {
            self::logModelChange('UPDATE', $model);
        });

        static::deleted(function (Model $model) {
            self::logModelChange('DELETE', $model);
        });
    }

    protected static function logModelChange(string $action, Model $model): void
    {
        $auditLogService = app(AuditLogService::class);

        $oldValues = null;
        $newValues = null;

        if ($action === 'UPDATE') {
            $oldValues = self::getDirtyValues($model->getOriginal(), $model);
            $newValues = self::getDirtyValues($model->getChanges(), $model);
        } elseif ($action === 'CREATE') {
            $newValues = self::getDirtyValues($model->getAttributes(), $model);
        } elseif ($action === 'DELETE') {
            $oldValues = self::getDirtyValues($model->getAttributes(), $model);
        }

        $auditLogService->logModelEvent($action, $model, $oldValues, $newValues);
    }

    protected static function getDirtyValues(array $values, Model $model): array
    {
        $exclude = property_exists($model, 'auditExclude') ? $model->auditExclude : [];

        $filtered = array_diff_key($values, array_flip($exclude));

        return $filtered;
    }

    public function getAuditModuleName(): string
    {
        return property_exists($this, 'auditModuleName') ? $this->auditModuleName : get_class($this);
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'subject');
    }
}
