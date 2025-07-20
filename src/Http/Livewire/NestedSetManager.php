<?php

namespace Indigoram89\NestedSet\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Model;
use Indigoram89\NestedSet\Models\NestedSetModel;

class NestedSetManager extends Component
{
    use WithPagination;

    public string $model_class = '';
    public array $tree_data = [];
    public bool $show_create_modal = false;
    public bool $show_edit_modal = false;
    public ?int $editing_id = null;
    public string $name = '';
    public ?string $slug = '';
    public ?int $parent_id = null;
    public string $search = '';

    protected $rules = [
        'name' => 'required|string|max:255',
        'slug' => 'nullable|string|max:255',
        'parent_id' => 'nullable|exists:nested_sets,id',
    ];

    public function mount(string $model_class = null): void
    {
        $this->model_class = $model_class ?? NestedSetModel::class;
        $this->loadTree();
    }

    public function loadTree(): void
    {
        $model = $this->getModelInstance();
        
        if ($this->search) {
            // При поиске показываем плоский список
            $items = $model::query()
                ->where('name', 'like', "%{$this->search}%")
                ->orderBy($model->getLeftColumn())
                ->get();
            
            $this->tree_data = $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'slug' => $item->slug,
                    'depth' => $item->depth,
                    'has_children' => false,
                    'expanded' => false,
                ];
            })->toArray();
        } else {
            // Без поиска показываем дерево
            $items = $model::query()->orderBy($model->getLeftColumn())->get();
            $this->tree_data = $this->buildTreeArray($items);
        }
    }

    protected function buildTreeArray($items, $parent_id = null, $depth = 0): array
    {
        $tree = [];
        
        foreach ($items as $item) {
            if ($item->parent_id == $parent_id) {
                $node = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'slug' => $item->slug,
                    'depth' => $depth,
                    'has_children' => !$item->isLeaf(),
                    'expanded' => true,
                ];
                
                $children = $this->buildTreeArray($items, $item->id, $depth + 1);
                if (!empty($children)) {
                    $node['children'] = $children;
                }
                
                $tree[] = $node;
            }
        }
        
        return $tree;
    }

    public function reorder(array $items): void
    {
        $model = $this->getModelInstance();
        
        \DB::transaction(function () use ($items, $model) {
            $this->processReorderItems($items, null, $model);
        });
        
        $this->loadTree();
        $this->dispatch('tree-updated');
    }

    protected function processReorderItems(array $items, ?int $parent_id, Model $model): void
    {
        $position = 0;
        
        foreach ($items as $item_data) {
            $item = $model::find($item_data['id']);
            
            if (!$item) {
                continue;
            }
            
            if ($position === 0 && $parent_id) {
                $parent = $model::find($parent_id);
                $item->makeChildOf($parent);
            } elseif ($position > 0) {
                $previous_item = $model::find($items[$position - 1]['id']);
                $item->moveToRightOf($previous_item);
            }
            
            if (isset($item_data['children']) && is_array($item_data['children'])) {
                $this->processReorderItems($item_data['children'], $item->id, $model);
            }
            
            $position++;
        }
    }

    public function create(): void
    {
        $this->resetForm();
        $this->show_create_modal = true;
    }

    public function store(): void
    {
        $this->validate();
        
        $model = $this->getModelInstance();
        
        \DB::transaction(function () use ($model) {
            $item = new $model([
                'name' => $this->name,
                'slug' => $this->slug ?: \Str::slug($this->name),
            ]);
            
            if ($this->parent_id) {
                $parent = $model::find($this->parent_id);
                $item->save();
                $item->makeChildOf($parent);
            } else {
                $item->save();
                $item->makeRoot();
            }
        });
        
        $this->show_create_modal = false;
        $this->resetForm();
        $this->loadTree();
        $this->dispatch('item-created');
    }

    public function edit(int $id): void
    {
        $model = $this->getModelInstance();
        $item = $model::findOrFail($id);
        
        $this->editing_id = $id;
        $this->name = $item->name;
        $this->slug = $item->slug ?? '';
        $this->parent_id = $item->parent_id;
        $this->show_edit_modal = true;
    }

    public function update(): void
    {
        $this->validate();
        
        $model = $this->getModelInstance();
        $item = $model::findOrFail($this->editing_id);
        
        \DB::transaction(function () use ($item) {
            $item->update([
                'name' => $this->name,
                'slug' => $this->slug ?: \Str::slug($this->name),
            ]);
            
            if ($item->parent_id !== $this->parent_id) {
                if ($this->parent_id) {
                    $parent = $item::find($this->parent_id);
                    $item->makeChildOf($parent);
                } else {
                    $item->makeRoot();
                }
            }
        });
        
        $this->show_edit_modal = false;
        $this->resetForm();
        $this->loadTree();
        $this->dispatch('item-updated');
    }

    public function delete(int $id): void
    {
        $model = $this->getModelInstance();
        $item = $model::findOrFail($id);
        
        \DB::transaction(function () use ($item) {
            $item->deleteSubtree();
        });
        
        $this->loadTree();
        $this->dispatch('item-deleted');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->loadTree();
    }

    protected function resetForm(): void
    {
        $this->editing_id = null;
        $this->name = '';
        $this->slug = '';
        $this->parent_id = null;
    }

    protected function getModelInstance(): Model
    {
        return new $this->model_class;
    }

    public function render()
    {
        return view('nested-set::livewire.nested-set-manager');
    }
}