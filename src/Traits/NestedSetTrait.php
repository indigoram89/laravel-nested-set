<?php

namespace Indigoram89\NestedSet\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Attributes\Scope;

trait NestedSetTrait
{
    protected bool $nested_set_updating = false;

    public static function bootNestedSetTrait(): void
    {
        static::creating(function (Model $model) {
            if (!$model->nested_set_updating) {
                $model->setDefaultLeftAndRight();
            }
        });

        static::deleting(function (Model $model) {
            if (!$model->nested_set_updating) {
                $model->deleteDescendants();
            }
        });
    }

    protected function setDefaultLeftAndRight(): void
    {
        $maxRight = static::max($this->getRightColumn()) ?? 0;
        $this->{$this->getLeftColumn()} = $maxRight + 1;
        $this->{$this->getRightColumn()} = $maxRight + 2;
        $this->{$this->getDepthColumn()} = 0;
    }

    public function getLeftColumn(): string
    {
        return config('nested-set.columns.left', 'lft');
    }

    public function getRightColumn(): string
    {
        return config('nested-set.columns.right', 'rgt');
    }

    public function getDepthColumn(): string
    {
        return config('nested-set.columns.depth', 'depth');
    }

    public function getParentIdColumn(): string
    {
        return config('nested-set.columns.parent_id', 'parent_id');
    }

    public function makeRoot(): self
    {
        return DB::transaction(function () {
            $this->nested_set_updating = true;
            
            $this->{$this->getParentIdColumn()} = null;
            $this->{$this->getDepthColumn()} = 0;
            
            if (!$this->exists) {
                $this->setDefaultLeftAndRight();
                $this->save();
            } else {
                $this->save();
            }
            
            $this->nested_set_updating = false;
            
            return $this;
        });
    }

    public function makeChildOf(Model $parent): self
    {
        $this->{$this->getParentIdColumn()} = $parent->id;
        
        $lastChild = $parent->getLastChild();
        if ($lastChild) {
            return $this->moveToRightOf($lastChild);
        }
        
        return $this->moveToRightOf($parent);
    }

    public function moveToLeftOf(Model $target): self
    {
        return $this->moveTo($target->{$this->getLeftColumn()}, 'left');
    }

    public function moveToRightOf(Model $target): self
    {
        if ($target->isRoot() && $target->isLeaf()) {
            // Вставка первого дочернего элемента корневого узла
            return $this->moveTo($target->{$this->getLeftColumn()} + 1, 'left');
        }
        return $this->moveTo($target->{$this->getRightColumn()} + 1, 'right');
    }

    protected function moveTo(int $position, string $boundary = 'left'): self
    {
        return DB::transaction(function () use ($position) {
            $this->nested_set_updating = true;
            
            // Если узел еще не сохранен, сохраняем его
            if (!$this->exists) {
                $this->save();
            }
            
            $left = $this->{$this->getLeftColumn()};
            $right = $this->{$this->getRightColumn()};
            $width = $right - $left + 1;
            
            // Временно делаем значения отрицательными
            static::query()
                ->where($this->getLeftColumn(), '>=', $left)
                ->where($this->getRightColumn(), '<=', $right)
                ->update([
                    $this->getLeftColumn() => DB::raw('-' . $this->getLeftColumn()),
                    $this->getRightColumn() => DB::raw('-' . $this->getRightColumn()),
                ]);
            
            // Закрываем промежуток на старом месте
            $this->shiftLeftRight($right + 1, 0, -$width);
            
            // Корректируем позицию если необходимо
            if ($position > $right) {
                $position -= $width;
            }
            
            // Открываем место на новой позиции
            $this->shiftLeftRight($position, 0, $width);
            
            // Перемещаем узел на новую позицию
            $offset = $position - $left;
            static::query()
                ->where($this->getLeftColumn(), '<', 0)
                ->update([
                    $this->getLeftColumn() => DB::raw('-' . $this->getLeftColumn() . ' + ' . $offset),
                    $this->getRightColumn() => DB::raw('-' . $this->getRightColumn() . ' + ' . $offset),
                ]);
            
            $this->reload();
            $this->updateDepth();
            
            $this->nested_set_updating = false;
            
            return $this;
        });
    }

    protected function shiftLeftRight(int $from, int $to, int $delta): void
    {
        if ($delta === 0) {
            return;
        }
        
        if ($from > 0) {
            static::query()
                ->where($this->getLeftColumn(), '>=', $from)
                ->update([$this->getLeftColumn() => DB::raw($this->getLeftColumn() . ' + ' . $delta)]);
        }
        
        if ($from > 0 || $to > 0) {
            static::query()
                ->where($this->getRightColumn(), '>=', ($to > $from ? $to : $from))
                ->update([$this->getRightColumn() => DB::raw($this->getRightColumn() . ' + ' . $delta)]);
        }
    }

    protected function updateDepth(): void
    {
        $parent = $this->determineParent();
        
        $this->nested_set_updating = true;
        
        if ($parent) {
            $this->{$this->getParentIdColumn()} = $parent->id;
            $this->{$this->getDepthColumn()} = $parent->{$this->getDepthColumn()} + 1;
        } else {
            $this->{$this->getParentIdColumn()} = null;
            $this->{$this->getDepthColumn()} = 0;
        }
        
        $this->save();
        $this->nested_set_updating = false;
        
        $this->getDescendants()->each(function ($descendant) {
            $descendant->updateDepth();
        });
    }
    
    protected function determineParent(): ?Model
    {
        return static::query()
            ->where($this->getLeftColumn(), '<', $this->{$this->getLeftColumn()})
            ->where($this->getRightColumn(), '>', $this->{$this->getRightColumn()})
            ->orderBy($this->getLeftColumn(), 'desc')
            ->first();
    }

    public function getLevel(): int
    {
        return $this->{$this->getDepthColumn()};
    }

    public function getDescendants(): Collection
    {
        return static::query()
            ->where($this->getLeftColumn(), '>', $this->{$this->getLeftColumn()})
            ->where($this->getRightColumn(), '<', $this->{$this->getRightColumn()})
            ->orderBy($this->getLeftColumn())
            ->get();
    }

    public function getAncestors(): Collection
    {
        return static::query()
            ->where($this->getLeftColumn(), '<', $this->{$this->getLeftColumn()})
            ->where($this->getRightColumn(), '>', $this->{$this->getRightColumn()})
            ->orderBy($this->getLeftColumn())
            ->get();
    }

    public function getSiblings(): Collection
    {
        $parent = $this->getParent();
        
        if (!$parent) {
            return static::query()
                ->whereNull($this->getParentIdColumn())
                ->where('id', '!=', $this->id)
                ->orderBy($this->getLeftColumn())
                ->get();
        }
        
        return $parent->getChildren()->where('id', '!=', $this->id);
    }

    public function getChildren(): Collection
    {
        return static::query()
            ->where($this->getParentIdColumn(), $this->id)
            ->orderBy($this->getLeftColumn())
            ->get();
    }

    public function getParent(): ?Model
    {
        return $this->belongsTo(static::class, $this->getParentIdColumn())->first();
    }

    public function getLastChild(): ?Model
    {
        return $this->getChildren()->last();
    }

    public function isLeaf(): bool
    {
        return $this->{$this->getRightColumn()} - $this->{$this->getLeftColumn()} === 1;
    }

    public function isRoot(): bool
    {
        return is_null($this->{$this->getParentIdColumn()});
    }

    public function getTree(): Collection
    {
        return static::query()
            ->orderBy($this->getLeftColumn())
            ->get();
    }

    public function getPath(): Collection
    {
        return $this->getAncestors()->push($this);
    }

    protected function deleteDescendants(): void
    {
        $left = $this->{$this->getLeftColumn()};
        $right = $this->{$this->getRightColumn()};
        $width = $right - $left + 1;
        
        static::query()
            ->where($this->getLeftColumn(), '>=', $left)
            ->where($this->getRightColumn(), '<=', $right)
            ->delete();
        
        $this->shiftLeftRight($right + 1, 0, -$width);
    }

    public function deleteSubtree(): bool
    {
        return DB::transaction(function () {
            $this->nested_set_updating = true;
            return $this->delete();
        });
    }

    #[Scope]
    public function roots(Builder $query): Builder
    {
        return $query->whereNull($this->getParentIdColumn());
    }

    #[Scope]
    public function leaves(Builder $query): Builder
    {
        return $query->whereRaw($this->getRightColumn() . ' - ' . $this->getLeftColumn() . ' = 1');
    }

    #[Scope]
    public function withDepth(Builder $query, int $depth): Builder
    {
        return $query->where($this->getDepthColumn(), $depth);
    }

    public function reload(): void
    {
        $fresh = $this->fresh();
        if ($fresh) {
            $this->setRawAttributes($fresh->getAttributes());
        }
    }

    public function rebuild(): void
    {
        DB::transaction(function () {
            $left = 1;
            $this->rebuildTree(static::query()->roots()->get(), 0, $left);
        });
    }

    protected function rebuildTree(Collection $nodes, int $depth, int &$left = 1): void
    {
        foreach ($nodes as $node) {
            $node->nested_set_updating = true;
            
            $node->{$this->getLeftColumn()} = $left++;
            $node->{$this->getDepthColumn()} = $depth;
            
            if ($node->getChildren()->isNotEmpty()) {
                $this->rebuildTree($node->getChildren(), $depth + 1, $left);
            }
            
            $node->{$this->getRightColumn()} = $left++;
            $node->save();
            
            $node->nested_set_updating = false;
        }
    }

    public function isValidNestedSet(): bool
    {
        $nodes = static::query()->orderBy($this->getLeftColumn())->get();
        
        if ($nodes->isEmpty()) {
            return true;
        }
        
        $stack = [];
        
        foreach ($nodes as $node) {
            $left = $node->{$this->getLeftColumn()};
            $right = $node->{$this->getRightColumn()};
            
            if ($left >= $right) {
                return false;
            }
            
            while (!empty($stack) && $stack[count($stack) - 1] < $right) {
                array_pop($stack);
            }
            
            $stack[] = $right;
        }
        
        return count($stack) === 1;
    }
}