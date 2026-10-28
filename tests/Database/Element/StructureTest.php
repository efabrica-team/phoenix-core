<?php

declare(strict_types=1);

namespace Phoenix\Tests\Database\Element;

use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\Element\MigrationView;
use Phoenix\Database\Element\Structure;
use Phoenix\Database\Element\Table;
use PHPUnit\Framework\TestCase;

final class StructureTest extends TestCase
{
    public function testEmpty(): void
    {
        $structure = new Structure();
        $this->assertEquals([], $structure->getTables());
        $this->assertNull($structure->getTable('some_table'));
        $this->assertEquals([], $structure->getViews());
        $this->assertFalse($structure->viewExists('some_view'));
    }

    public function testViews(): void
    {
        $structure = new Structure();
        $this->assertFalse($structure->viewExists('test_view'));

        $this->assertInstanceOf(Structure::class, $structure->addView('test_view'));
        $this->assertTrue($structure->viewExists('test_view'));
        $this->assertEquals(['test_view'], $structure->getViews());

        $structure->addView('test_view');
        $this->assertCount(1, $structure->getViews());

        $createdView = new MigrationView('created_view');
        $createdView->sql('SELECT 1');
        $createdView->create();
        $this->assertInstanceOf(Structure::class, $structure->updateView($createdView));
        $this->assertTrue($structure->viewExists('created_view'));

        $replacedView = new MigrationView('replaced_view');
        $replacedView->sql('SELECT 1');
        $replacedView->replace();
        $structure->updateView($replacedView);
        $this->assertTrue($structure->viewExists('replaced_view'));
        $this->assertEquals(['test_view', 'created_view', 'replaced_view'], $structure->getViews());

        $droppedView = new MigrationView('created_view');
        $droppedView->drop();
        $structure->updateView($droppedView);
        $this->assertFalse($structure->viewExists('created_view'));
        $this->assertEquals(['test_view', 'replaced_view'], $structure->getViews());
    }

    public function testAddSimpleTable(): void
    {
        $structure = new Structure();

        $this->assertCount(0, $structure->getTables());
        $this->assertEquals([], $structure->getTables());
        $this->assertNull($structure->getTable('test'));

        $migrationTable = new MigrationTable('test');
        $migrationTable->addColumn('title', 'string');
        $migrationTable->create();
        $this->assertInstanceOf(Structure::class, $structure->update($migrationTable));
        $this->assertCount(1, $structure->getTables());
        $table = $structure->getTable('test');
        $this->assertInstanceOf(Table::class, $table);
        $this->assertEquals('test', $table->getName());
        $this->assertCount(2, $table->getColumns());
        $idColumn = $table->getColumn('id');
        $this->assertInstanceOf(Column::class, $idColumn);
        $titleColumn = $table->getColumn('title');
        $this->assertInstanceOf(Column::class, $titleColumn);
    }
}
