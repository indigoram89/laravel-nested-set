# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Package Overview

This is a Laravel package for managing hierarchical data using the Nested Set pattern. It includes a modern Vue.js 3 web interface with drag-and-drop support via Sortable.js.

## Development Commands

### Install Dependencies
```bash
composer install
```

### Run Tests
```bash
# Run all tests
vendor/bin/phpunit

# Run specific test file
vendor/bin/phpunit tests/Unit/NestedSetTraitTest.php

# Run specific test method
vendor/bin/phpunit --filter="test_can_create_root_node"

# Run with debug output
vendor/bin/phpunit --debug
```

### Create New Release
```bash
# Patch version (1.0.0 → 1.0.1)
./release.sh patch "Bug fixes"

# Minor version (1.0.1 → 1.1.0)
./release.sh minor "New features"

# Major version (1.1.0 → 2.0.0)
./release.sh major "Breaking changes"
```

## Core Architecture

### Nested Set Pattern Implementation

The package implements the Modified Preorder Tree Traversal algorithm where each node has:
- `lft` (left) and `rgt` (right) values
- `depth` level in the tree
- `parent_id` for direct parent reference

Key invariants:
- All descendants have lft/rgt values between parent's lft and rgt
- rgt > lft always
- If rgt - lft = 1, the node is a leaf

### Core Components

1. **NestedSetTrait** (`src/Traits/NestedSetTrait.php`)
   - Contains all Nested Set logic
   - Methods for tree manipulation (makeRoot, makeChildOf, moveToLeftOf, etc.)
   - Query scopes (roots, leaves, withDepth)
   - Tree validation and rebuilding

2. **NestedSetModel** (`src/Models/NestedSetModel.php`)
   - Abstract base model with trait pre-applied
   - Default configuration

3. **Web Interface** (`resources/js/nested-set-standalone.js`)
   - Vue.js 3 application for UI management
   - Handles drag-and-drop via Sortable.js
   - CRUD operations with REST API

4. **API Controller** (`src/Http/Controllers/NestedSetApiController.php`)
   - REST API endpoints for tree operations
   - Handles models, tree, CRUD, and reorder operations

5. **Service Provider** (`src/NestedSetServiceProvider.php`)
   - Publishes config, migrations, views, and assets
   - Loads API and web routes

### Critical Implementation Details

1. **Transaction Safety**: All tree modifications use database transactions to ensure atomicity

2. **Nested Set Updates**: The `nested_set_updating` property prevents recursive updates during tree operations

3. **Tree Reordering**: When moving nodes, the algorithm:
   - Makes node values temporarily negative
   - Closes the gap at the old position
   - Opens space at the new position
   - Moves the node with proper offset calculations

4. **Parent Determination**: After moves, parent_id is recalculated based on lft/rgt containment via `determineParent()`

5. **Depth Updates**: The `updateDepth()` method recursively updates depth values after tree changes

## Testing Approach

- Unit tests cover all NestedSetTrait methods
- Tests use SQLite in-memory database
- Key test scenarios: node creation, movement, deletion, tree integrity

## Package Configuration

The package expects these database columns:
- `lft`, `rgt` (integers)
- `depth` (unsigned integer)
- `parent_id` (unsigned bigint, nullable)

Column names are configurable via `config/nested-set.php`.

## Language Context

- All user-facing text is in Russian
- Code comments are in Russian
- Variable names use snake_case per project convention