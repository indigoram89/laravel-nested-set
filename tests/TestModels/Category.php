<?php

namespace Indigoram89\NestedSet\Tests\TestModels;

use Indigoram89\NestedSet\Models\NestedSetModel;

class Category extends NestedSetModel
{
    protected $table = 'nested_sets';
    
    protected $fillable = ['name', 'slug'];
}