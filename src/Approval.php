<?php

namespace Victorlap\Approvable;

use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;

class Approval extends Eloquent
{
    public $table = 'approvals';

    protected $casts = [
        'approved_by' => 'integer',
        'rejected_by' => 'integer',
        'user_id'     => 'integer'
    ];
    protected $dates = [
        'created_at',
        'updated_at',
        'approved_at',
        'rejected_at'
    ];

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getFieldName(): string
    {
        return $this->key;
    }

    /**
     *
     * @param $overrideApprovable Approvable used when accepting a change on a new model
     * @throws \Exception
     */
    public function accept($overrideApprovable = null): void
    {
        if ($overrideApprovable) {
            $approvable          = $overrideApprovable;
            $this->approvable_id = $overrideApprovable->id;
        } else {
            $approvable = $this->approvable;
        }

        $approvable->withoutApproval();
        $approvable->{$this->getFieldName()} = $this->new_value;
        $approvable->save();
        $approvable->withApproval();

        $this->approved_at = new DateTime();
        $this->approved_by = Auth::id();
        $this->save();
    }

    public function reject(): void
    {
        $this->rejected_at = new DateTime();
        $this->rejected_by = Auth::id();
        $this->save();
    }

    public function scopeOpen($query): Builder
    {
        return $query->whereNull('approved_at')
            ->whereNull('rejected_at');
    }

    public function scopeRejected($query): Builder
    {
        return $query->whereNotNull('rejected_at');
    }

    public function scopeAccepted($query): Builder
    {
        return $query->whereNotNull('approved_at');
    }

    public function scopeOfClass($query, $class): Builder
    {
        return $query->where('approvable_type', $class);
    }

    public function scopeOfId($query, $id): Builder
    {
        return $query->where('approvable_id', $id);
    }

    public function scopeByUser($query, $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeLatest($query): Builder
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeNewAndLinkedWith($query, $linkKey, $linkValue): Builder
    {
        return $query->whereIn('batch', function (QueryBuilder $subQuery) use ($linkKey, $linkValue) {

            $subQuery->select('batch')
                ->from($this->table)
                ->whereNull('approvable_id')
                ->where('key', $linkKey)
                ->where('new_value', $linkValue);
        });
    }
}
