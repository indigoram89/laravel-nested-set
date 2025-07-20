<?php

namespace Indigoram89\NestedSet\Models;

use Illuminate\Database\Eloquent\Model;
use Indigoram89\NestedSet\Traits\NestedSetTrait;

abstract class NestedSetModel extends Model
{
    use NestedSetTrait;

    protected $fillable = [
        'name',
        'slug',
        'parent_id',
    ];

    protected $casts = [
        'lft' => 'integer',
        'rgt' => 'integer',
        'depth' => 'integer',
        'parent_id' => 'integer',
    ];

    public function getTable()
    {
        return $this->table ?? config('nested-set.table', 'nested_sets');
    }
}