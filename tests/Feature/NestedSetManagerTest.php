<?php

namespace Indigoram89\NestedSet\Tests\Feature;

use Indigoram89\NestedSet\Tests\TestCase;
use Indigoram89\NestedSet\Tests\TestModels\Category;
use Indigoram89\NestedSet\Http\Livewire\NestedSetManager;
use Livewire\Livewire;

class NestedSetManagerTest extends TestCase
{
    public function test_can_render_component()
    {
        Livewire::test(NestedSetManager::class, ['model_class' => Category::class])
            ->assertStatus(200)
            ->assertSee('Управление деревом');
    }

    public function test_can_create_root_item()
    {
        Livewire::test(NestedSetManager::class, ['model_class' => Category::class])
            ->call('create')
            ->assertSet('show_create_modal', true)
            ->set('name', 'Test Category')
            ->set('slug', 'test-category')
            ->call('store')
            ->assertSet('show_create_modal', false)
            ->assertDispatched('item-created');

        $this->assertDatabaseHas('nested_sets', [
            'name' => 'Test Category',
            'slug' => 'test-category',
            'parent_id' => null,
        ]);

        $category = Category::where('name', 'Test Category')->first();
        $this->assertEquals(1, $category->lft);
        $this->assertEquals(2, $category->rgt);
        $this->assertEquals(0, $category->depth);
    }

    public function test_can_create_child_item()
    {
        $parent = Category::create(['name' => 'Parent']);
        $parent->makeRoot();

        Livewire::test(NestedSetManager::class, ['model_class' => Category::class])
            ->call('create')
            ->set('name', 'Child Category')
            ->set('parent_id', $parent->id)
            ->call('store')
            ->assertDispatched('item-created');

        $child = Category::where('name', 'Child Category')->first();
        $this->assertEquals($parent->id, $child->parent_id);
        $this->assertEquals(2, $child->lft);
        $this->assertEquals(3, $child->rgt);
        $this->assertEquals(1, $child->depth);
    }

    public function test_can_edit_item()
    {
        $category = Category::create(['name' => 'Original Name']);
        $category->makeRoot();

        Livewire::test(NestedSetManager::class, ['model_class' => Category::class])
            ->call('edit', $category->id)
            ->assertSet('show_edit_modal', true)
            ->assertSet('editing_id', $category->id)
            ->assertSet('name', 'Original Name')
            ->set('name', 'Updated Name')
            ->set('slug', 'updated-name')
            ->call('update')
            ->assertSet('show_edit_modal', false)
            ->assertDispatched('item-updated');

        $this->assertDatabaseHas('nested_sets', [
            'id' => $category->id,
            'name' => 'Updated Name',
            'slug' => 'updated-name',
        ]);
    }

    public function test_can_delete_item()
    {
        $category = Category::create(['name' => 'To Delete']);
        $category->makeRoot();

        Livewire::test(NestedSetManager::class, ['model_class' => Category::class])
            ->call('delete', $category->id)
            ->assertDispatched('item-deleted');

        $this->assertDatabaseMissing('nested_sets', [
            'id' => $category->id,
        ]);
    }

    public function test_can_search_items()
    {
        $category1 = Category::create(['name' => 'Apple']);
        $category1->makeRoot();

        $category2 = Category::create(['name' => 'Banana']);
        $category2->makeRoot();

        $category3 = Category::create(['name' => 'Cherry']);
        $category3->makeRoot();

        $component = Livewire::test(NestedSetManager::class, ['model_class' => Category::class]);
        
        // Проверяем что изначально видны все элементы
        $component->assertSee('Apple')
            ->assertSee('Banana')
            ->assertSee('Cherry');
        
        // Устанавливаем поиск и проверяем фильтрацию
        $component->set('search', 'Ban');
        
        // Проверяем что видны только элементы содержащие "Ban"
        $component->assertSee('Banana');
        
        // Проверяем что элементы НЕ содержащие "Ban" скрыты
        // В выводе все еще могут быть упоминания в селектах модальных окон
        // поэтому проверяем структуру дерева
        $treeData = $component->get('tree_data');
        $this->assertCount(1, $treeData);
        $this->assertEquals('Banana', $treeData[0]['name']);
    }

    public function test_can_reorder_items()
    {
        $root = Category::create(['name' => 'Root']);
        $root->makeRoot();

        $child1 = Category::create(['name' => 'Child 1']);
        $child1->makeChildOf($root);

        $child2 = Category::create(['name' => 'Child 2']);
        $child2->makeChildOf($root);

        $reorderData = [
            ['id' => $root->id, 'children' => [
                ['id' => $child2->id],
                ['id' => $child1->id],
            ]]
        ];

        Livewire::test(NestedSetManager::class, ['model_class' => Category::class])
            ->call('reorder', $reorderData)
            ->assertDispatched('tree-updated');

        $child1 = $child1->fresh();
        $child2 = $child2->fresh();

        $this->assertEquals(4, $child1->lft);
        $this->assertEquals(5, $child1->rgt);
        $this->assertEquals(2, $child2->lft);
        $this->assertEquals(3, $child2->rgt);
    }

    public function test_validates_required_fields()
    {
        Livewire::test(NestedSetManager::class, ['model_class' => Category::class])
            ->call('create')
            ->set('name', '')
            ->call('store')
            ->assertHasErrors(['name' => 'required']);
    }

    public function test_auto_generates_slug_if_empty()
    {
        Livewire::test(NestedSetManager::class, ['model_class' => Category::class])
            ->call('create')
            ->set('name', 'Test Category Name')
            ->set('slug', '')
            ->call('store');

        $this->assertDatabaseHas('nested_sets', [
            'name' => 'Test Category Name',
            'slug' => 'test-category-name',
        ]);
    }

    public function test_displays_tree_structure()
    {
        $root = Category::create(['name' => 'Root Category']);
        $root->makeRoot();

        $child = Category::create(['name' => 'Child Category']);
        $child->makeChildOf($root);

        $grandchild = Category::create(['name' => 'Grandchild Category']);
        $grandchild->makeChildOf($child);

        Livewire::test(NestedSetManager::class, ['model_class' => Category::class])
            ->assertSee('Root Category')
            ->assertSee('Child Category')
            ->assertSee('Grandchild Category');
    }
}