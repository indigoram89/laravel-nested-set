<?php

namespace Indigoram89\NestedSet\Tests\Unit;

use Indigoram89\NestedSet\Tests\TestCase;
use Indigoram89\NestedSet\Tests\TestModels\Category;

class NestedSetTraitTest extends TestCase
{
    public function test_can_create_root_node()
    {
        $root = new Category(['name' => 'Root']);
        $root->save();
        $root->makeRoot();

        $this->assertEquals(1, $root->lft);
        $this->assertEquals(2, $root->rgt);
        $this->assertEquals(0, $root->depth);
        $this->assertNull($root->parent_id);
        $this->assertTrue($root->isRoot());
        $this->assertTrue($root->isLeaf());
    }

    public function test_can_create_child_node()
    {
        $root = Category::create(['name' => 'Root']);
        $root->makeRoot();

        $child = Category::create(['name' => 'Child']);
        $child->makeChildOf($root);

        $this->assertEquals(1, $root->fresh()->lft);
        $this->assertEquals(4, $root->fresh()->rgt);
        $this->assertEquals(2, $child->fresh()->lft);
        $this->assertEquals(3, $child->fresh()->rgt);
        $this->assertEquals(1, $child->fresh()->depth);
        $this->assertEquals($root->id, $child->fresh()->parent_id);
        $this->assertFalse($root->fresh()->isLeaf());
        $this->assertTrue($child->fresh()->isLeaf());
    }

    public function test_can_create_multiple_children()
    {
        $root = Category::create(['name' => 'Root']);
        $root->makeRoot();

        $child1 = Category::create(['name' => 'Child 1']);
        $child1->makeChildOf($root);

        $child2 = Category::create(['name' => 'Child 2']);
        $child2->makeChildOf($root);

        $root = $root->fresh();
        $child1 = $child1->fresh();
        $child2 = $child2->fresh();

        $this->assertEquals(1, $root->lft);
        $this->assertEquals(6, $root->rgt);
        $this->assertEquals(2, $child1->lft);
        $this->assertEquals(3, $child1->rgt);
        $this->assertEquals(4, $child2->lft);
        $this->assertEquals(5, $child2->rgt);
    }

    public function test_can_move_node_to_left_of_sibling()
    {
        $root = Category::create(['name' => 'Root']);
        $root->makeRoot();

        $child1 = Category::create(['name' => 'Child 1']);
        $child1->makeChildOf($root);

        $child2 = Category::create(['name' => 'Child 2']);
        $child2->makeChildOf($root);

        $child2->moveToLeftOf($child1);

        $child1 = $child1->fresh();
        $child2 = $child2->fresh();

        $this->assertEquals(4, $child1->lft);
        $this->assertEquals(5, $child1->rgt);
        $this->assertEquals(2, $child2->lft);
        $this->assertEquals(3, $child2->rgt);
    }

    public function test_can_move_node_to_right_of_sibling()
    {
        $root = Category::create(['name' => 'Root']);
        $root->makeRoot();

        $child1 = Category::create(['name' => 'Child 1']);
        $child1->makeChildOf($root);

        $child2 = Category::create(['name' => 'Child 2']);
        $child2->makeChildOf($root);

        $child1->moveToRightOf($child2);

        $child1 = $child1->fresh();
        $child2 = $child2->fresh();

        $this->assertEquals(4, $child1->lft);
        $this->assertEquals(5, $child1->rgt);
        $this->assertEquals(2, $child2->lft);
        $this->assertEquals(3, $child2->rgt);
    }

    public function test_can_get_descendants()
    {
        $root = Category::create(['name' => 'Root']);
        $root->makeRoot();

        $child1 = Category::create(['name' => 'Child 1']);
        $child1->makeChildOf($root);

        $grandchild = Category::create(['name' => 'Grandchild']);
        $grandchild->makeChildOf($child1);

        // Обновляем из БД чтобы получить актуальные lft/rgt
        $root = $root->fresh();
        
        $descendants = $root->getDescendants();

        $this->assertCount(2, $descendants);
        $this->assertEquals('Child 1', $descendants[0]->name);
        $this->assertEquals('Grandchild', $descendants[1]->name);
    }

    public function test_can_get_ancestors()
    {
        $root = Category::create(['name' => 'Root']);
        $root->makeRoot();

        $child = Category::create(['name' => 'Child']);
        $child->makeChildOf($root);

        $grandchild = Category::create(['name' => 'Grandchild']);
        $grandchild->makeChildOf($child);

        // Обновляем из БД чтобы получить актуальные lft/rgt
        $grandchild = $grandchild->fresh();
        
        $ancestors = $grandchild->getAncestors();

        $this->assertCount(2, $ancestors);
        $this->assertEquals('Root', $ancestors[0]->name);
        $this->assertEquals('Child', $ancestors[1]->name);
    }

    public function test_can_get_siblings()
    {
        $root = Category::create(['name' => 'Root']);
        $root->makeRoot();

        $child1 = Category::create(['name' => 'Child 1']);
        $child1->makeChildOf($root);

        $child2 = Category::create(['name' => 'Child 2']);
        $child2->makeChildOf($root);

        $child3 = Category::create(['name' => 'Child 3']);
        $child3->makeChildOf($root);

        $siblings = $child2->getSiblings();

        $this->assertCount(2, $siblings);
        $this->assertTrue($siblings->contains('name', 'Child 1'));
        $this->assertTrue($siblings->contains('name', 'Child 3'));
        $this->assertFalse($siblings->contains('name', 'Child 2'));
    }

    public function test_can_delete_node_with_descendants()
    {
        $root = Category::create(['name' => 'Root']);
        $root->makeRoot();

        $child = Category::create(['name' => 'Child']);
        $child->makeChildOf($root);

        $grandchild = Category::create(['name' => 'Grandchild']);
        $grandchild->makeChildOf($child);

        // Проверяем структуру перед удалением
        $root = $root->fresh();
        $child = $child->fresh();
        $grandchild = $grandchild->fresh();
        
        $this->assertEquals($root->id, $child->parent_id);
        $this->assertEquals($child->id, $grandchild->parent_id);

        // Сохраняем id до удаления
        $childId = $child->id;
        $grandchildId = $grandchild->id;

        $child->deleteSubtree();

        $this->assertNull(Category::find($childId));
        $this->assertNull(Category::find($grandchildId));
        $this->assertNotNull(Category::find($root->id));

        $root = $root->fresh();
        $this->assertEquals(1, $root->lft);
        $this->assertEquals(2, $root->rgt);
    }

    public function test_can_get_tree()
    {
        $root1 = Category::create(['name' => 'Root 1']);
        $root1->makeRoot();

        $root2 = Category::create(['name' => 'Root 2']);
        $root2->makeRoot();

        $child = Category::create(['name' => 'Child']);
        $child->makeChildOf($root1);

        $tree = (new Category)->getTree();

        $this->assertCount(3, $tree);
        $this->assertEquals('Root 1', $tree[0]->name);
        $this->assertEquals('Child', $tree[1]->name);
        $this->assertEquals('Root 2', $tree[2]->name);
    }

    public function test_can_rebuild_tree()
    {
        $root = Category::create(['name' => 'Root']);
        $child1 = Category::create(['name' => 'Child 1']);
        $child2 = Category::create(['name' => 'Child 2']);

        $root->lft = 1;
        $root->rgt = 2;
        $root->depth = 0;
        $root->save();

        $child1->parent_id = $root->id;
        $child1->lft = 3;
        $child1->rgt = 4;
        $child1->depth = 1;
        $child1->save();

        $child2->parent_id = $root->id;
        $child2->lft = 5;
        $child2->rgt = 6;
        $child2->depth = 1;
        $child2->save();

        $root->rebuild();

        $root = $root->fresh();
        $child1 = $child1->fresh();
        $child2 = $child2->fresh();

        $this->assertEquals(1, $root->lft);
        $this->assertEquals(6, $root->rgt);
        $this->assertEquals(2, $child1->lft);
        $this->assertEquals(3, $child1->rgt);
        $this->assertEquals(4, $child2->lft);
        $this->assertEquals(5, $child2->rgt);
    }

    public function test_validates_nested_set_integrity()
    {
        $root = Category::create(['name' => 'Root']);
        $root->makeRoot();

        $child = Category::create(['name' => 'Child']);
        $child->makeChildOf($root);

        $this->assertTrue((new Category)->isValidNestedSet());

        // Испортим структуру
        $child->nested_set_updating = true;
        $child->lft = 10;
        $child->save();
        $child->nested_set_updating = false;

        $this->assertFalse((new Category)->isValidNestedSet());
    }

    public function test_scope_roots()
    {
        $root1 = Category::create(['name' => 'Root 1']);
        $root1->makeRoot();

        $root2 = Category::create(['name' => 'Root 2']);
        $root2->makeRoot();

        $child = Category::create(['name' => 'Child']);
        $child->makeChildOf($root1);

        $roots = Category::query()->roots()->get();

        $this->assertCount(2, $roots);
        $this->assertTrue($roots->contains('name', 'Root 1'));
        $this->assertTrue($roots->contains('name', 'Root 2'));
        $this->assertFalse($roots->contains('name', 'Child'));
    }

    public function test_scope_leaves()
    {
        $root = Category::create(['name' => 'Root']);
        $root->makeRoot();

        $child1 = Category::create(['name' => 'Child 1']);
        $child1->makeChildOf($root);

        $child2 = Category::create(['name' => 'Child 2']);
        $child2->makeChildOf($root);

        $grandchild = Category::create(['name' => 'Grandchild']);
        $grandchild->makeChildOf($child1);

        $leaves = Category::query()->leaves()->get();

        $this->assertCount(2, $leaves);
        $this->assertTrue($leaves->contains('name', 'Child 2'));
        $this->assertTrue($leaves->contains('name', 'Grandchild'));
        $this->assertFalse($leaves->contains('name', 'Root'));
        $this->assertFalse($leaves->contains('name', 'Child 1'));
    }
}